<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calcule les horaires et le statut d'ouverture du restaurant.
 *
 * Les horaires proviennent de config/reservations.php par défaut. Un jour
 * peut être personnalisé ou fermé depuis config/restaurant.php sans toucher
 * à la logique des réservations.
 */
class RestaurantSchedule
{
    public function timezone(): string
    {
        return (string) config('restaurant.timezone', config('app.timezone', 'Africa/Casablanca'));
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /**
     * Horaires du jour demandé, ou null si le restaurant est fermé ce jour-là.
     *
     * @return array{open: string, close: string}|null
     */
    public function hoursFor(CarbonInterface $day): ?array
    {
        $dayKey = strtolower($day->copy()->locale('en')->englishDayOfWeek);

        if (in_array($dayKey, (array) config('restaurant.closed_days', []), true)) {
            return null;
        }

        $custom = config('restaurant.hours.'.$dayKey);

        if (is_array($custom) && isset($custom['open'], $custom['close'])) {
            return [
                'open' => (string) $custom['open'],
                'close' => (string) $custom['close'],
            ];
        }

        return [
            'open' => (string) config('reservations.opening_time', '08:00'),
            'close' => (string) config('reservations.closing_time', '22:00'),
        ];
    }

    public function isOpenAt(?CarbonInterface $moment = null): bool
    {
        $moment = $this->normalize($moment);
        $hours = $this->hoursFor($moment);

        if ($hours === null) {
            return false;
        }

        [$open, $close] = $this->window($moment, $hours);

        return $moment->greaterThanOrEqualTo($open) && $moment->lessThan($close);
    }

    public function nextOpeningFrom(?CarbonInterface $moment = null): ?CarbonImmutable
    {
        $moment = $this->normalize($moment);
        $cursor = $moment->startOfDay();

        // Huit jours suffisent pour couvrir une semaine complète de fermeture.
        for ($i = 0; $i < 8; $i++) {
            $hours = $this->hoursFor($cursor);

            if ($hours !== null) {
                [$open] = $this->window($cursor, $hours);

                if ($open->greaterThan($moment)) {
                    return $open;
                }
            }

            $cursor = $cursor->addDay();
        }

        return null;
    }

    /**
     * Statut prêt à afficher dans la vue.
     *
     * @return array{is_open: bool, label: string, detail: string, next_opening: ?CarbonImmutable}
     */
    public function status(?CarbonInterface $moment = null): array
    {
        $moment = $this->normalize($moment);
        $hours = $this->hoursFor($moment);
        $isOpen = $hours !== null && $this->isOpenAt($moment);

        if ($isOpen) {
            [, $close] = $this->window($moment, $hours);

            return [
                'is_open' => true,
                'label' => 'Ouvert',
                'detail' => 'Ferme à '.$this->formatTime($close->format('H:i')),
                'next_opening' => null,
            ];
        }

        $next = $this->nextOpeningFrom($moment);

        if ($next === null) {
            $detail = 'Horaires à confirmer';
        } elseif ($next->isSameDay($moment)) {
            $detail = "Ouvre aujourd'hui à ".$this->formatTime($next->format('H:i'));
        } elseif ($next->isSameDay($moment->addDay())) {
            $detail = 'Ouvre demain à '.$this->formatTime($next->format('H:i'));
        } else {
            $detail = 'Ouvre '.$next->locale('fr')->translatedFormat('l').' à '.$this->formatTime($next->format('H:i'));
        }

        return [
            'is_open' => false,
            'label' => 'Fermé',
            'detail' => $detail,
            'next_opening' => $next,
        ];
    }

    public function hoursLabel(?CarbonInterface $day = null): string
    {
        $day ??= $this->now();
        $hours = $this->hoursFor($day);

        if ($hours === null) {
            return "Fermé aujourd'hui";
        }

        return $this->formatTime($hours['open']).' – '.$this->formatTime($hours['close']);
    }

    public function formatTime(string $time): string
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '00');

        $hours = (int) $hours;
        $minutes = str_pad(substr($minutes, 0, 2), 2, '0', STR_PAD_LEFT);

        return $minutes === '00' ? $hours.'h' : $hours.'h'.$minutes;
    }

    private function normalize(?CarbonInterface $moment = null): CarbonImmutable
    {
        $moment = $moment === null
            ? $this->now()
            : CarbonImmutable::instance($moment);

        return $moment->setTimezone($this->timezone());
    }

    /**
     * @param  array{open: string, close: string}  $hours
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(CarbonInterface $day, array $hours): array
    {
        $timezone = $this->timezone();
        $date = $day->toDateString();

        $open = CarbonImmutable::parse($date.' '.$hours['open'], $timezone);
        $close = CarbonImmutable::parse($date.' '.$hours['close'], $timezone);

        // Gère une éventuelle fermeture après minuit.
        if ($close->lessThanOrEqualTo($open)) {
            $close = $close->addDay();
        }

        return [$open, $close];
    }
}
