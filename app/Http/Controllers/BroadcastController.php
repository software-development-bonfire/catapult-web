<?php

namespace App\Http\Controllers;

use App\Events\MyPrivateEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastController extends Controller
{
    /**
     * Broadcast a specified event with given parameters.
     * Example request payload:
     * POST /api/broadcast-event
     *   { 
     *     "event": "App\\Events\\ComputeEngine",
     *     "params": [123, "Order has been created!"]
     *   }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function broadcastEvent(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'params' => 'nullable|array',
            'channels' => 'nullable|array', // e.g. ["orders", "updates"]
            'channel_type' => 'nullable|string|in:public,private,presence', // default: public
            'broadcast_name' => 'nullable|string' // Custom event name
        ]);

        $eventClass = $request->input('event');
        $params = $request->input('params', []);
        $channels = $request->input('channels', []);
        $channelType = $request->input('channel_type', 'public');
        $broadcastName = $request->input('broadcast_name');

        if (!class_exists($eventClass)) {
            return response()->json(['error' => 'Event class not found'], 404);
        }

        if (!in_array(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, class_implements($eventClass))) {
            return response()->json(['error' => 'Event does not implement ShouldBroadcast'], 400);
        }

        //try {
        $reflection = new ReflectionClass($eventClass);
        $event = $reflection->newInstanceArgs($params);

        // ✅ Dynamically override broadcastOn() if channels are provided
        if (!empty($channels)) {
            $event->broadcastOn = function () use ($channels, $channelType) {
                return collect($channels)->map(function ($ch) use ($channelType) {
                    switch ($channelType) {
                        case 'private':
                            return new \Illuminate\Broadcasting\PrivateChannel($ch);
                        case 'presence':
                            return new \Illuminate\Broadcasting\PresenceChannel($ch);
                        default:
                            return new \Illuminate\Broadcasting\Channel($ch);
                    }
                })->toArray();
            };
        }

        // ✅ Dynamically override broadcastAs() if provided
        if (!empty($broadcastName)) {
            $event->broadcastAs = function () use ($broadcastName) {
                return $broadcastName;
            };
        }

        broadcast($event);

        return response()->json([
            'status' => 'Event dispatched',
            'event' => $eventClass,
            'params' => $params,
            'channels' => $channels,
            'channel_type' => $channelType,
            'broadcast_name' => $broadcastName
        ]);
        //} catch (\Throwable $e) {
        //   return response()->json(['error' => 'Failed to dispatch event: ' . $e->getMessage()], 500);
        // }
    }

    public function broadcastOTSRequestStatusEvent(Request $request)
    {
        $request->validate([
            'data' => 'nullable|array',
            'device_id' => 'required|string',
            'request_type' => 'nullable|string'
        ]);

        $data = $request->input('data', []);
        $requestStatus = $request->input('request_type', 'status');
        $deviceId = $request->input('device_id');
        \broadcast(new \App\Events\OTS\OTSRequestStatusEvent($requestStatus, $deviceId, $data));
        broadcast(new MyPrivateEvent('Device123', ['id' => 1, 'amount' => 100.50], [['item' => 'Product A', 'qty' => 2]], false));

        return response()->json([
            'status' => 'Event dispatched',
            'request' => [
                'request_type' => $requestStatus,
                'device_id' => $deviceId,
                'data' => $data
            ]
        ]);
    }
}
