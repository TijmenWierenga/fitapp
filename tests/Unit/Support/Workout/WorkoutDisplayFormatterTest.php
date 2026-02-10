<?php

use App\Support\Workout\WorkoutDisplayFormatter as Format;

describe('setsReps', function () {
    it('formats sets and rep range', function () {
        expect(Format::setsReps(3, 8, 12))->toBe('3 sets of 8-12 reps');
    });

    it('formats sets and single rep count when min equals max', function () {
        expect(Format::setsReps(3, 10, 10))->toBe('3 sets of 10 reps');
    });

    it('formats sets and reps when min is null', function () {
        expect(Format::setsReps(3, null, 10))->toBe('3 sets of 10 reps');
    });

    it('formats sets only when no reps', function () {
        expect(Format::setsReps(3, null, null))->toBe('3 sets');
    });

    it('formats reps only when no sets', function () {
        expect(Format::setsReps(null, null, 12))->toBe('12 reps');
    });

    it('formats rep range without sets', function () {
        expect(Format::setsReps(null, 8, 12))->toBe('8-12 reps');
    });

    it('returns null when all values are null', function () {
        expect(Format::setsReps(null, null, null))->toBeNull();
    });
});

describe('weight', function () {
    it('formats whole number weight', function () {
        expect(Format::weight(80.00))->toBe('80 kg');
    });

    it('formats decimal weight', function () {
        expect(Format::weight(80.5))->toBe('80.5 kg');
    });

    it('formats string weight', function () {
        expect(Format::weight('22.50'))->toBe('22.5 kg');
    });

    it('returns null for null', function () {
        expect(Format::weight(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::weight(0.0))->toBeNull();
    });
});

describe('duration', function () {
    it('formats seconds', function () {
        expect(Format::duration(30))->toBe('30s');
    });

    it('formats minutes', function () {
        expect(Format::duration(300))->toBe('5min');
    });

    it('formats hours', function () {
        expect(Format::duration(3600))->toBe('1h');
    });

    it('formats combined time', function () {
        expect(Format::duration(3661))->toBe('1h 1min 1s');
    });

    it('returns null for null', function () {
        expect(Format::duration(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::duration(0))->toBeNull();
    });
});

describe('distance', function () {
    it('formats meters', function () {
        expect(Format::distance(500))->toBe('500 m');
    });

    it('formats kilometers', function () {
        expect(Format::distance(5000))->toBe('5 km');
    });

    it('returns null for null', function () {
        expect(Format::distance(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::distance(0))->toBeNull();
    });
});

describe('paceRange', function () {
    it('formats single pace', function () {
        expect(Format::paceRange(330, 330))->toBe('5:30 /km');
    });

    it('formats pace range', function () {
        expect(Format::paceRange(330, 360))->toBe('5:30-6:00 /km');
    });

    it('formats single min pace when max is null', function () {
        expect(Format::paceRange(330, null))->toBe('5:30 /km');
    });

    it('formats single max pace when min is null', function () {
        expect(Format::paceRange(null, 360))->toBe('6:00 /km');
    });

    it('returns null when both are null', function () {
        expect(Format::paceRange(null, null))->toBeNull();
    });
});

describe('hrZone', function () {
    it('formats heart rate zone', function () {
        expect(Format::hrZone(3))->toBe('Zone 3');
    });

    it('returns null for null', function () {
        expect(Format::hrZone(null))->toBeNull();
    });
});

describe('hrRange', function () {
    it('formats heart rate range', function () {
        expect(Format::hrRange(140, 160))->toBe('140-160 bpm');
    });

    it('formats single min value', function () {
        expect(Format::hrRange(140, null))->toBe('140 bpm');
    });

    it('formats single max value', function () {
        expect(Format::hrRange(null, 160))->toBe('160 bpm');
    });

    it('returns null when both are null', function () {
        expect(Format::hrRange(null, null))->toBeNull();
    });
});

describe('power', function () {
    it('formats watts', function () {
        expect(Format::power(250))->toBe('250 W');
    });

    it('returns null for null', function () {
        expect(Format::power(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::power(0))->toBeNull();
    });
});

describe('intervals', function () {
    it('formats work and rest intervals', function () {
        expect(Format::intervals(30, 15))->toBe('30s on / 15s off');
    });

    it('formats work only', function () {
        expect(Format::intervals(60, null))->toBe('1min on');
    });

    it('formats rest only', function () {
        expect(Format::intervals(null, 30))->toBe('30s off');
    });

    it('returns null when both are null', function () {
        expect(Format::intervals(null, null))->toBeNull();
    });
});

describe('rpe', function () {
    it('formats RPE with effort label', function () {
        expect(Format::rpe(7.5))->toBe('RPE 7.5 (Hard)');
    });

    it('formats whole number RPE', function () {
        expect(Format::rpe(6.0))->toBe('RPE 6 (Moderate)');
    });

    it('formats string RPE', function () {
        expect(Format::rpe('3.0'))->toBe('RPE 3 (Easy)');
    });

    it('formats very easy RPE', function () {
        expect(Format::rpe(1.0))->toBe('RPE 1 (Very Easy)');
    });

    it('formats maximum effort RPE', function () {
        expect(Format::rpe(9.5))->toBe('RPE 9.5 (Maximum Effort)');
    });

    it('returns null for null', function () {
        expect(Format::rpe(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::rpe(0.0))->toBeNull();
    });
});

describe('rpeLabel', function () {
    it('labels very easy', function () {
        expect(Format::rpeLabel(2.0))->toBe('Very Easy');
    });

    it('labels easy', function () {
        expect(Format::rpeLabel(4.0))->toBe('Easy');
    });

    it('labels moderate', function () {
        expect(Format::rpeLabel(5.0))->toBe('Moderate');
    });

    it('labels hard', function () {
        expect(Format::rpeLabel(8.0))->toBe('Hard');
    });

    it('labels maximum effort', function () {
        expect(Format::rpeLabel(10.0))->toBe('Maximum Effort');
    });
});

describe('rest', function () {
    it('formats rest period as plain time', function () {
        expect(Format::rest(90))->toBe('1min 30s');
    });

    it('returns null for null', function () {
        expect(Format::rest(null))->toBeNull();
    });

    it('returns null for zero', function () {
        expect(Format::rest(0))->toBeNull();
    });
});
