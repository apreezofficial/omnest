<?php

declare(strict_types=1);

use Omnest\Controllers\AuthController;
use Omnest\Controllers\ChildController;
use Omnest\Controllers\DeviceController;
use Omnest\Controllers\HealthController;
use Omnest\Http\Router;

return static function (Router $router): void {
    $router->get('/health', [HealthController::class, 'show']);

    $router->group(['prefix' => '/api/v1', 'middleware' => ['throttle.api']], static function (Router $r): void {
        $r->get('/health', [HealthController::class, 'show']);

        // Parent accounts
        $r->group(['prefix' => '/auth'], static function (Router $r): void {
            $r->post('/register', [AuthController::class, 'register'], ['throttle.auth']);
            $r->post('/login', [AuthController::class, 'login'], ['throttle.auth']);
            $r->post('/email/verify', [AuthController::class, 'verifyEmail'], ['throttle.auth']);
            $r->post('/password/forgot', [AuthController::class, 'forgotPassword'], ['throttle.auth']);
            $r->post('/password/reset', [AuthController::class, 'resetPassword'], ['throttle.auth']);

            $r->group(['middleware' => ['auth.parent']], static function (Router $r): void {
                $r->get('/me', [AuthController::class, 'me']);
                $r->post('/logout', [AuthController::class, 'logout']);
                $r->post('/email/resend', [AuthController::class, 'resendVerification'], ['throttle.auth']);
            });
        });

        // Parent: children and their devices
        $r->group(['middleware' => ['auth.parent']], static function (Router $r): void {
            $r->get('/children', [ChildController::class, 'index']);
            $r->post('/children', [ChildController::class, 'store']);
            $r->get('/children/{id}', [ChildController::class, 'show']);
            $r->patch('/children/{id}', [ChildController::class, 'update']);
            $r->delete('/children/{id}', [ChildController::class, 'destroy']);
            $r->get('/children/{id}/devices', [ChildController::class, 'devices']);
            $r->post('/children/{id}/pairing-codes', [ChildController::class, 'createPairingCode']);
            $r->delete('/devices/{id}', [DeviceController::class, 'revoke']);
        });

        // Child phone
        $r->group(['prefix' => '/device'], static function (Router $r): void {
            $r->post('/pair', [DeviceController::class, 'pair'], ['throttle.pair']);

            $r->group(['middleware' => ['auth.device']], static function (Router $r): void {
                $r->get('/me', [DeviceController::class, 'me']);
                $r->put('/fcm-token', [DeviceController::class, 'updateFcmToken']);
            });
        });
    });
};
