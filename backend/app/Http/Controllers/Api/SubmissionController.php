<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\User;
use App\Services\AirQualityService;
use App\Services\PythonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use OpenApi\Annotations as OA;

class SubmissionController extends Controller
{
    protected $airQualityService;
    protected $pythonService;

    public function __construct(AirQualityService $airQualityService, PythonService $pythonService)
    {
        $this->airQualityService = $airQualityService;
        $this->pythonService = $pythonService;
    }

    /**
     * @OA\Post(
     *     path="/submissions",
     *     tags={"Submissions"},
     *     summary="Submit a photo with air quality guess",
     *     description="Submit a photo with user's guess of air quality level and location coordinates",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photo", "user_guess", "latitude", "longitude"},
     *                 @OA\Property(
     *                     property="photo",
     *                     type="string",
     *                     format="binary",
     *                     description="Photo file (JPEG, PNG, JPG, GIF, max 10MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="user_guess",
     *                     type="integer",
     *                     minimum=1,
     *                     maximum=5,
     *                     description="User's guess of air quality level (1-5)",
     *                     example=3
     *                 ),
     *                 @OA\Property(
     *                     property="latitude",
     *                     type="number",
     *                     format="float",
     *                     minimum=-90,
     *                     maximum=90,
     *                     description="Latitude coordinate",
     *                     example=40.7128
     *                 ),
     *                 @OA\Property(
     *                     property="longitude",
     *                     type="number",
     *                     format="float",
     *                     minimum=-180,
     *                     maximum=180,
     *                     description="Longitude coordinate",
     *                     example=-74.0060
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="user_guess", type="integer", example=3),
     *                 @OA\Property(property="actual_level", type="integer", example=3),
     *                 @OA\Property(property="accuracy_score", type="integer", example=5),
     *                 @OA\Property(property="photo_url", type="string", example="http://localhost:8000/storage/submissions/photo.jpg"),
     *                 @OA\Property(property="forecast", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to process submission"),
     *             @OA\Property(property="error", type="string", example="Service unavailable")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'user_guess' => 'required|integer|min:1|max:5',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get authenticated user (or create anonymous user)
            $user = Auth::user() ?? $this->createAnonymousUser();

            // Store the uploaded photo
            $photoPath = $this->storePhoto($request->file('photo'));

            // Create submission record
            $submission = Submission::create([
                'user_id' => $user->id,
                'photo_path' => $photoPath,
                'user_guess' => $request->user_guess,
                'location_lat' => $request->latitude,
                'location_lng' => $request->longitude,
                'submitted_at' => now(),
            ]);

            // Get air quality data from Python service
            $airQualityData = $this->pythonService->getAirQualityData([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Calculate accuracy score
            $actualLevel = $airQualityData['current_aqi'] ?? 3;
            $accuracyScore = $this->calculateAccuracyScore($request->user_guess, $actualLevel);

            // Update submission with results
            $submission->update([
                'actual_level' => $actualLevel,
                'accuracy_score' => $accuracyScore,
                'air_quality_data' => json_encode($airQualityData),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $submission->id,
                    'user_guess' => $submission->user_guess,
                    'actual_level' => $submission->actual_level,
                    'accuracy_score' => $submission->accuracy_score,
                    'photo_url' => Storage::url($submission->photo_path),
                    'forecast' => $airQualityData['forecast'] ?? [],
                    'submitted_at' => $submission->submitted_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/submissions",
     *     tags={"Submissions"},
     *     summary="Get user's submission history",
     *     description="Retrieves paginated list of user's photo submissions",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="user_guess", type="integer", example=3),
     *                     @OA\Property(property="actual_level", type="integer", example=3),
     *                     @OA\Property(property="accuracy_score", type="integer", example=5),
     *                     @OA\Property(property="photo_url", type="string", example="http://localhost:8000/storage/submissions/photo.jpg"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $submissions = Submission::where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'pagination' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/submissions/{id}",
     *     tags={"Submissions"},
     *     summary="Get specific submission details",
     *     description="Retrieves detailed information about a specific submission",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="user_guess", type="integer", example=3),
     *                 @OA\Property(property="actual_level", type="integer", example=3),
     *                 @OA\Property(property="accuracy_score", type="integer", example=5),
     *                 @OA\Property(property="photo_url", type="string", example="http://localhost:8000/storage/submissions/photo.jpg"),
     *                 @OA\Property(property="location_lat", type="number", format="float", example=40.7128),
     *                 @OA\Property(property="location_lng", type="number", format="float", example=-74.0060),
     *                 @OA\Property(property="air_quality_data", type="object"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Submission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Submission not found")
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $submission = Submission::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'user_guess' => $submission->user_guess,
                'actual_level' => $submission->actual_level,
                'accuracy_score' => $submission->accuracy_score,
                'photo_url' => Storage::url($submission->photo_path),
                'location_lat' => $submission->location_lat,
                'location_lng' => $submission->location_lng,
                'air_quality_data' => json_decode($submission->air_quality_data, true),
                'submitted_at' => $submission->submitted_at,
            ]
        ]);
    }

    /**
     * Store uploaded photo and return path
     */
    private function storePhoto($photo): string
    {
        $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $path = 'submissions/' . $filename;
        
        // Store original photo
        Storage::disk('public')->put($path, file_get_contents($photo));
        
        // Create thumbnail
        $thumbnailPath = 'submissions/thumbnails/' . $filename;
        $thumbnail = Image::make($photo)->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        Storage::disk('public')->put($thumbnailPath, $thumbnail->encode());
        
        return $path;
    }

    /**
     * Create anonymous user for unauthenticated submissions
     */
    private function createAnonymousUser(): User
    {
        return User::create([
            'name' => 'Anonymous User',
            'email' => 'anonymous_' . uniqid() . '@apollo14.local',
            'password' => bcrypt(uniqid()),
            'is_anonymous' => true,
        ]);
    }

    /**
     * Calculate accuracy score based on guess vs actual
     */
    private function calculateAccuracyScore(int $userGuess, int $actualLevel): int
    {
        $difference = abs($userGuess - $actualLevel);
        
        // Perfect guess = 5 points
        if ($difference === 0) return 5;
        
        // Off by 1 = 4 points
        if ($difference === 1) return 4;
        
        // Off by 2 = 3 points
        if ($difference === 2) return 3;
        
        // Off by 3 = 2 points
        if ($difference === 3) return 2;
        
        // Off by 4 = 1 point
        return 1;
    }
}
