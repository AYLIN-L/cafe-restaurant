<?php

use App\Mail\ReservationAbsenceWarningMail;
use App\Mail\ReservationCancelledByAdminMail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationCreatedMail;
use App\Mail\ReservationRejectedMail;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\TableSeeder;
use Illuminate\Support\Facades\Mail;

function emailClient(): User
{
    return User::factory()->create(['role' => 'client']);
}

function emailAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('sends a reservation created email to the client', function () {
    Mail::fake();
    $this->seed(TableSeeder::class);
    $client = emailClient();
    $this->actingAs($client);

    $response = $this->post(route('reservations.store'), [
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
    ]);

    $response->assertStatus(302);
    Mail::assertSent(ReservationCreatedMail::class, function ($mail) use ($client) {
        return $mail->hasTo($client->email);
    });
    Mail::assertSent(ReservationCreatedMail::class, 1);
});

it('sends confirmation email with assigned table number on admin confirmation', function () {
    Mail::fake();
    $this->seed(TableSeeder::class);
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed'])
        ->assertSessionHas('success');

    $reservation->refresh();
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->table_id)->not->toBeNull();

    Mail::assertSent(ReservationConfirmedMail::class, function ($mail) use ($client, $reservation) {
        if (! $mail->hasTo($client->email)) {
            return false;
        }
        $tableNumber = $mail->tableNumber ?? ($reservation->table ? $reservation->table->number : null);

        return $tableNumber !== null;
    });
    Mail::assertSent(ReservationConfirmedMail::class, 1);
});

it('does not send confirmation email if already confirmed', function () {
    Mail::fake();
    $this->seed(TableSeeder::class);
    $admin = emailAdmin();
    $client = emailClient();
    $table = Table::first();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'table_id' => $table->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'confirmed']);

    Mail::assertNotSent(ReservationConfirmedMail::class);
});

it('sends rejection email on admin rejection', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'rejected'])
        ->assertSessionHas('success');

    Mail::assertSent(ReservationRejectedMail::class, function ($mail) use ($client) {
        return $mail->hasTo($client->email);
    });
    Mail::assertSent(ReservationRejectedMail::class, 1);
});

it('sends cancellation by admin email and not on client cancellation', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.status', $reservation), ['status' => 'cancelled'])
        ->assertSessionHas('success');

    Mail::assertSent(ReservationCancelledByAdminMail::class, function ($mail) use ($client) {
        return $mail->hasTo($client->email);
    });
    Mail::assertSent(ReservationCancelledByAdminMail::class, 1);

    Mail::fake();
    $client2 = emailClient();
    $reservation2 = Reservation::create([
        'user_id' => $client2->id,
        'reservation_date' => now()->addDays(2)->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($client2)
        ->patch(route('reservations.cancel', $reservation2))
        ->assertSessionHas('success');

    Mail::assertNotSent(ReservationCancelledByAdminMail::class);
});

it('sends absence warning emails for 1st through 5th absence and blocks at 5th', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $threshold = (int) config('reservations.absence_threshold', 5);

    for ($i = 0; $i < $threshold - 1; $i++) {
        $res = Reservation::create([
            'user_id' => $client->id,
            'reservation_date' => now()->subDays($threshold - $i)->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
            'status' => Reservation::STATUS_CONFIRMED,
            'active_date' => null,
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ]);
    }

    expect($client->fresh()->absencesCount())->toBe($threshold - 1);
    expect($client->fresh()->isBookingBlocked())->toBeFalse();

    Mail::fake();
    $last = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $last), ['attendance' => Reservation::ATTENDANCE_ABSENT])
        ->assertSessionHas('success');

    expect($client->fresh()->absencesCount())->toBe($threshold);
    expect($client->fresh()->isBookingBlocked())->toBeTrue();
    Mail::assertSent(ReservationAbsenceWarningMail::class, function ($mail) use ($client) {
        return $mail->hasTo($client->email);
    });
    Mail::assertSent(ReservationAbsenceWarningMail::class, 1);
});

it('does not send duplicate absence warning for the same recorded absence', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    Mail::assertSent(ReservationAbsenceWarningMail::class, 1);

    Mail::fake();
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), ['attendance' => Reservation::ATTENDANCE_ABSENT]);
    Mail::assertNotSent(ReservationAbsenceWarningMail::class);
});

it('blocked client cannot create new reservation', function () {
    Mail::fake();
    $client = emailClient();
    $client->blockBooking();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');
});

it('sends one warning per absence level with the matching count', function () {
    $admin = emailAdmin();
    $threshold = (int) config('reservations.absence_threshold', 5);

    for ($level = 1; $level <= $threshold; $level++) {
        Mail::fake();
        $client = emailClient();

        // Absences déjà enregistrées avant celle que l'on va marquer.
        for ($previous = 1; $previous < $level; $previous++) {
            Reservation::create([
                'user_id' => $client->id,
                'reservation_date' => now()->subDays(30 + $previous)->toDateString(),
                'reservation_time' => '12:00',
                'guests_count' => 2,
                'status' => Reservation::STATUS_CONFIRMED,
                'active_date' => null,
                'attendance' => Reservation::ATTENDANCE_ABSENT,
            ]);
        }

        $reservation = Reservation::create([
            'user_id' => $client->id,
            'reservation_date' => now()->subDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
            'status' => Reservation::STATUS_CONFIRMED,
            'active_date' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.attendance', $reservation), [
                'attendance' => Reservation::ATTENDANCE_ABSENT,
            ])
            ->assertSessionHas('success');

        expect($client->fresh()->absencesCount())->toBe($level);
        Mail::assertSent(ReservationAbsenceWarningMail::class, function ($mail) use ($client, $level) {
            return $mail->hasTo($client->email) && $mail->absencesCount === $level;
        });
        Mail::assertSent(ReservationAbsenceWarningMail::class, 1);

        if ($level >= $threshold) {
            expect($client->fresh()->isBookingBlocked())->toBeTrue();
        } else {
            expect($client->fresh()->isBookingBlocked())->toBeFalse();
        }
    }
});

it('does not send a created email when the reservation fails validation', function () {
    Mail::fake();
    $this->seed(TableSeeder::class);
    $client = emailClient();
    $date = now()->addDay()->toDateString();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    Mail::assertSent(ReservationCreatedMail::class, 1);

    Mail::fake();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasErrors('reservation_date');

    Mail::assertNothingSent();
    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('keeps the reservation when the email transport fails', function () {
    $this->seed(TableSeeder::class);
    $client = emailClient();

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP indisponible'));

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('skips the email when the client has no email address', function () {
    Mail::fake();
    $this->seed(TableSeeder::class);
    $client = emailClient();
    $client->forceFill(['email' => ''])->saveQuietly();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    Mail::assertNothingSent();
    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('keeps the reservation when the client email is invalid', function () {
    $this->seed(TableSeeder::class);
    $client = emailClient();
    $client->forceFill(['email' => 'adresse-invalide'])->saveQuietly();

    $this->actingAs($client)
        ->post(route('reservations.store'), [
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'guests_count' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect(Reservation::where('user_id', $client->id)->count())->toBe(1);
});

it('does not send an email when the client cancels a reservation', function () {
    Mail::fake();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_PENDING,
        'active_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($client)
        ->patch(route('reservations.cancel', $reservation))
        ->assertSessionHas('success');

    expect($reservation->fresh()->status)->toBe(Reservation::STATUS_CANCELLED);
    Mail::assertNothingSent();
});

it('does not send an absence email when presence is recorded', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_PRESENT,
        ])
        ->assertSessionHas('success');

    Mail::assertNothingSent();
});

it('records the absence and blocks the client even if the warning email fails', function () {
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ]);

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP indisponible'));

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ])
        ->assertSessionHas('success');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_ABSENT);
    expect($client->fresh()->absencesCount())->toBe(1);
    expect($reservation->fresh()->absence_warning_sent_at)->not->toBeNull();
});

it('does not send a warning for an absence recorded on a hidden reservation', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
        'hidden_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ])
        ->assertSessionHas('success');

    expect($client->fresh()->absencesCount())->toBe(1);
    Mail::assertNothingSent();
});

it('does not resend a warning when an absence is toggled back to absent', function () {
    Mail::fake();
    $admin = emailAdmin();
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ]);
    Mail::assertSent(ReservationAbsenceWarningMail::class, 1);

    // Une absence déjà enregistrée ne peut plus être remplacée par une présence.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_PRESENT,
        ])
        ->assertSessionHas('error');

    expect($reservation->fresh()->attendance)->toBe(Reservation::ATTENDANCE_ABSENT);

    Mail::fake();

    // Rejeu identique (double clic) : accepté, sans effet ni nouvel e-mail.
    $this->actingAs($admin)
        ->patch(route('admin.reservations.attendance', $reservation), [
            'attendance' => Reservation::ATTENDANCE_ABSENT,
        ])
        ->assertSessionHas('success');

    Mail::assertNothingSent();
    expect($reservation->fresh()->absence_warning_sent_at)->not->toBeNull();
});

it('renders every html and text email template', function () {
    $this->seed(TableSeeder::class);
    $client = emailClient();
    $reservation = Reservation::create([
        'user_id' => $client->id,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '12:00',
        'guests_count' => 2,
        'status' => Reservation::STATUS_CONFIRMED,
        'active_date' => now()->addDay()->toDateString(),
        'table_id' => Table::first()->id,
    ]);

    $mailables = [
        new ReservationCreatedMail($reservation),
        new ReservationConfirmedMail($reservation),
        new ReservationRejectedMail($reservation, 'Complet'),
        new ReservationCancelledByAdminMail($reservation, 'Fermeture exceptionnelle'),
        new ReservationAbsenceWarningMail($client, 1, $reservation),
        new ReservationAbsenceWarningMail($client, 2, $reservation),
        new ReservationAbsenceWarningMail($client, 3, $reservation),
        new ReservationAbsenceWarningMail($client, 4, $reservation),
        new ReservationAbsenceWarningMail($client, 5, $reservation),
    ];

    foreach ($mailables as $mailable) {
        expect($mailable->render())->toContain('Café Restaurant');
        expect(view($mailable->textView, $mailable->buildViewData())->render())
            ->toContain('Café Restaurant');
    }
});
