<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Apollo14 Air Quality API",
 *     version="1.0.0",
 *     description="API for air quality monitoring and photo submissions",
 *     @OA\Contact(
 *         email="support@apollo14.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Air Quality",
 *     description="Air quality data endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Submissions",
 *     description="Photo submission endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Health",
 *     description="System health check"
 * )
 */
abstract class Controller
{
    //
}