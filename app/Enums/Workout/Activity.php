<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum Activity: string
{
    // Running
    case Run = 'run';
    case IndoorTrack = 'indoor_track';
    case TrackRun = 'track_run';
    case TrailRun = 'trail_run';
    case Treadmill = 'treadmill';
    case UltraRun = 'ultra_run';
    case ObstacleRacing = 'obstacle_racing';

    // Cycling
    case Bike = 'bike';
    case BikeIndoor = 'bike_indoor';
    case BikeTour = 'bike_tour';
    case GravelBike = 'gravel_bike';
    case MountainBike = 'mountain_bike';
    case RoadBike = 'road_bike';
    case EBike = 'e_bike';

    // Swimming
    case PoolSwim = 'pool_swim';
    case OpenWater = 'open_water';

    // Walking/Hiking
    case Walk = 'walk';
    case WalkIndoor = 'walk_indoor';
    case Hike = 'hike';
    case Rucking = 'rucking';
    case Mountaineering = 'mountaineering';

    // Gym
    case Strength = 'strength';
    case Cardio = 'cardio';
    case HIIT = 'hiit';
    case Elliptical = 'elliptical';
    case FloorClimb = 'floor_climb';
    case StairStepper = 'stair_stepper';
    case RowIndoor = 'row_indoor';
    case JumpRope = 'jump_rope';
    case ClimbIndoor = 'climb_indoor';

    // Flexibility
    case Yoga = 'yoga';
    case Pilates = 'pilates';
    case Mobility = 'mobility';

    // Combat
    case Boxing = 'boxing';
    case MixedMartialArts = 'mixed_martial_arts';

    // Racket Sports
    case Tennis = 'tennis';
    case Padel = 'padel';
    case Badminton = 'badminton';
    case Squash = 'squash';
    case TableTennis = 'table_tennis';
    case Pickleball = 'pickleball';
    case Racquetball = 'racquetball';

    // Water Sports
    case Row = 'row';
    case Kayak = 'kayak';
    case Surf = 'surf';
    case SUP = 'sup';
    case Sail = 'sail';
    case Windsurf = 'windsurf';

    // Winter Sports
    case Ski = 'ski';
    case Snowboard = 'snowboard';
    case BackcountrySki = 'backcountry_ski';
    case IceSkating = 'ice_skating';
    case Snowshoe = 'snowshoe';
    case XCClassicSki = 'xc_classic_ski';
    case XCSkateSki = 'xc_skate_ski';

    // Team Sports
    case Soccer = 'soccer';
    case Basketball = 'basketball';
    case Volleyball = 'volleyball';
    case Rugby = 'rugby';
    case AmericanFootball = 'american_football';
    case Baseball = 'baseball';
    case IceHockey = 'ice_hockey';
    case FieldHockey = 'field_hockey';
    case Lacrosse = 'lacrosse';
    case Cricket = 'cricket';
    case Softball = 'softball';
    case UltimateDisc = 'ultimate_disc';

    // Mind/Body
    case Meditation = 'meditation';
    case Breathwork = 'breathwork';

    // Multi-sport
    case Triathlon = 'triathlon';
    case Swimrun = 'swimrun';

    // Other
    case Golf = 'golf';
    case InlineSkating = 'inline_skating';
    case DiscGolf = 'disc_golf';
    case Archery = 'archery';
    case Horseback = 'horseback';
    case Fish = 'fish';
    case Other = 'other';

    public function category(): string
    {
        return match ($this) {
            self::Run, self::IndoorTrack, self::TrackRun, self::TrailRun, self::Treadmill, self::UltraRun, self::ObstacleRacing => 'running',
            self::Bike, self::BikeIndoor, self::BikeTour, self::GravelBike, self::MountainBike, self::RoadBike, self::EBike => 'cycling',
            self::PoolSwim, self::OpenWater => 'swimming',
            self::Walk, self::WalkIndoor, self::Hike, self::Rucking, self::Mountaineering => 'walking',
            self::Strength, self::Cardio, self::HIIT, self::Elliptical, self::FloorClimb, self::StairStepper, self::RowIndoor, self::JumpRope, self::ClimbIndoor => 'gym',
            self::Yoga, self::Pilates, self::Mobility => 'flexibility',
            self::Boxing, self::MixedMartialArts => 'combat',
            self::Tennis, self::Padel, self::Badminton, self::Squash, self::TableTennis, self::Pickleball, self::Racquetball => 'racket',
            self::Row, self::Kayak, self::Surf, self::SUP, self::Sail, self::Windsurf => 'water',
            self::Ski, self::Snowboard, self::BackcountrySki, self::IceSkating, self::Snowshoe, self::XCClassicSki, self::XCSkateSki => 'winter',
            self::Soccer, self::Basketball, self::Volleyball, self::Rugby, self::AmericanFootball, self::Baseball, self::IceHockey, self::FieldHockey, self::Lacrosse, self::Cricket, self::Softball, self::UltimateDisc => 'team',
            self::Meditation, self::Breathwork => 'mind_body',
            self::Triathlon, self::Swimrun => 'multi_sport',
            self::Golf, self::InlineSkating, self::DiscGolf, self::Archery, self::Horseback, self::Fish, self::Other => 'other',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::HIIT => 'HIIT',
            self::SUP => 'SUP',
            self::EBike => 'E-Bike',
            self::XCClassicSki => 'XC Classic Ski',
            self::XCSkateSki => 'XC Skate Ski',
            self::MixedMartialArts => 'MMA',
            default => str_replace('_', ' ', ucwords(str_replace('_', ' ', preg_replace('/(?<!^)([A-Z])/', ' $1', $this->name)))),
        };
    }

    public function icon(): string
    {
        return match ($this->category()) {
            'running' => 'bolt',
            'cycling' => 'arrow-path',
            'swimming' => 'beaker',
            'walking' => 'map',
            'gym' => 'fire',
            'flexibility' => 'sparkles',
            'combat' => 'shield-check',
            'racket' => 'signal',
            'water' => 'beaker',
            'winter' => 'cloud',
            'team' => 'user-group',
            'mind_body' => 'eye',
            'multi_sport' => 'arrows-right-left',
            default => 'ellipsis-horizontal',
        };
    }

    public function color(): string
    {
        return match ($this->category()) {
            'running' => 'blue',
            'cycling' => 'green',
            'swimming' => 'cyan',
            'walking' => 'lime',
            'gym' => 'orange',
            'flexibility' => 'pink',
            'combat' => 'red',
            'racket' => 'yellow',
            'water' => 'sky',
            'winter' => 'indigo',
            'team' => 'violet',
            'mind_body' => 'purple',
            'multi_sport' => 'amber',
            default => 'zinc',
        };
    }

    public function hasBlocks(): bool
    {
        return true;
    }
}
