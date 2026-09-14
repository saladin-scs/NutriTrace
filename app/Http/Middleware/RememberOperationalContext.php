<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps operational navigation context in the session (map → cold room → batch…).
 * Relies on the web stack StartSession middleware already being active.
 */
class RememberOperationalContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->routeIs('cold-rooms.show') && $request->route('coldRoom')) {
            $request->session()->put('ops.cold_room_id', $request->route('coldRoom')->id);
            $request->session()->put('ops.cold_room_code', $request->route('coldRoom')->code);
        }

        if ($request->routeIs('shipments.show') && $request->route('shipment')) {
            $request->session()->put('ops.shipment_id', $request->route('shipment')->id);
            $request->session()->put('ops.shipment_code', $request->route('shipment')->code);
        }

        if ($request->routeIs('batches.show') && $request->route('batch')) {
            $request->session()->put('ops.batch_id', $request->route('batch')->id);
            $request->session()->put('ops.batch_code', $request->route('batch')->code);
        }

        return $response;
    }
}
