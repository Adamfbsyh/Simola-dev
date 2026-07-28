<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetCompany;
use App\Models\FleetDistanceProfile;
use App\Models\FleetTerminal;
use App\Models\FleetVehicle;
use Illuminate\View\View;

class MasterFleetController extends Controller
{
    public function index(): View
    {
        return view(
            'master-fleet.index',
            [
                'terminalCount' =>
                    FleetTerminal::query()
                        ->count(),

                'activeTerminalCount' =>
                    FleetTerminal::query()
                        ->active()
                        ->count(),

                'companyCount' =>
                    FleetCompany::query()
                        ->count(),

                'activeCompanyCount' =>
                    FleetCompany::query()
                        ->active()
                        ->count(),

                'distanceProfileCount' =>
                    FleetDistanceProfile::query()
                        ->count(),

                'vehicleCount' =>
                    FleetVehicle::query()
                        ->count(),

                'activeVehicleCount' =>
                    FleetVehicle::query()
                        ->active()
                        ->count(),
            ]
        );
    }
}