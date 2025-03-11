<?php

use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use App\WebSockets\CustomWebSocketHandler;

WebSocketsRouter::webSocket('/app/{appKey}', CustomWebSocketHandler::class);
