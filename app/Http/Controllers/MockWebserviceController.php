<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MockWebserviceController extends Controller
{
    public function __invoke(Request $request)
    {
        if (rand(0, 1) === 0) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Ticket received successfully',
                'ticket_id' => $request->input('ticket_id'),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Internal server error',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
