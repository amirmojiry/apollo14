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
     * Store a new photo submission
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
     * Get user's submission history
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
     * Get specific submission details
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
