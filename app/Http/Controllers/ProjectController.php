<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\JoinProjectRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectController extends Controller
{

    public function index(): JsonResponse
    {
        $user = request()->user();

        $projects = $user->projects()->with(['members.user'])->get()->map(function ($project) use ($user) {
            $membership = $project->members()->where('user_id', $user->id)->first();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'subject' => $project->subject,
                'action_status' => $project->action_status,
                'deadline' => $project->deadline,
                'created_at' => $project->created_at,
                'role' => $membership->role,
                'is_owner' => $membership->is_owner,
                'members_count' => $project->members()->count(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $projects
        ]);
    }


    public function store(CreateProjectRequest $request): JsonResponse
    {
        try {
            Log::info('=== START PROJECT CREATION ===');
            $user = request()->user();
            Log::info('User ID:', [$user->id]);
            Log::info('Request data:', $request->all());

            $project = Project::create([
                'id' => Str::uuid(),
                'name' => $request->name,
                'description' => $request->description,
                'subject' => $request->subject,
                'deadline' => $request->deadline,
                'action_status' => 0,
            ]);

            Log::info('Project created:', [$project->id, $project->name]);

            Log::info('Creating membership...');
            $membershipData = [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'role' => 'BACKEND',
                'is_owner' => true,
            ];

            Log::info('Membership data:', $membershipData);

            $membership = ProjectMembership::create($membershipData);

            Log::info('Membership created:', [$membership->id]);
            Log::info('=== END PROJECT CREATION ===');

            return response()->json([
                'status' => 'success',
                'message' => 'Project created successfully',
                'data' => [
                    'project' => $project,
                    'join_code' => $project->id,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Project creation error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function join(JoinProjectRequest $request): JsonResponse
    {
        try {
            $user = request()->user();
            $project = Project::find($request->project_id);

            $existingMembership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->first();

            if ($existingMembership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already a member of this project'
                ], 409);
            }

            ProjectMembership::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'role' => 'BACKEND',
                'is_owner' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully joined the project',
                'data' => [
                    'project' => $project,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to join project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $project = Project::with(['members.user', 'tasks.owner', 'tasks.subtasks'])
                ->find($id);

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $project
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $id)
                ->where('is_owner', true)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only project owner can update the project'
                ], 403);
            }

            $project = Project::find($id);
            $projectId = $project->id;
            $tasksWithLaterDeadlines = Task::where('project_id', $projectId)

                ->whereNotNull('deadline')
                ->whereDate('deadline', '>', $request['deadline'])
                ->exists();
            if ($tasksWithLaterDeadlines) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot set project deadline earlier than existing task deadlines'
                ], 400);
            }

            if (isset($request['action_status']) && $request['action_status'] == '2') {
                $hasUncompletedTasks = Task::where('project_id', $project->id)
                    ->where('status', '!=', 2)
                    ->exists();

                if ($hasUncompletedTasks) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot mark project as completed while it has uncompleted tasks'
                    ], 400);
                }
            }

            $project->update(request()->only(['name', 'description', 'subject', 'deadline', 'action_status']));

            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'data' => $project
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $id)
                ->where('is_owner', true)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only project owner can delete the project'
                ], 403);
            }

            Project::destroy($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function removeMember(string $projectId, string $userId): JsonResponse
    {
        try {
            $user = request()->user();

            $ownerMembership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->where('is_owner', true)
                ->first();

            if (!$ownerMembership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only project owner can remove members'
                ], 403);
            }

            $memberToRemove = ProjectMembership::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->first();

            if (!$memberToRemove) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member not found'
                ], 404);
            }

            if ($memberToRemove->is_owner) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot remove project owner'
                ], 400);
            }

            $memberToRemove->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Member removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function members(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $members = ProjectMembership::with('user')
                ->where('project_id', $id)
                ->get()
                ->map(function ($membership) {
                    return [
                        'user_id' => $membership->user_id,
                        'email' => $membership->user->email,
                        'role' => $membership->role,
                        'is_owner' => $membership->is_owner,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $members
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get project members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMemberRole(Request $request, string $projectId, string $userId): JsonResponse
    {
        try {
            $user = request()->user();

            $ownerMembership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->where('is_owner', true)
                ->first();

            if (!$ownerMembership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only project owner can change member roles'
                ], 403);
            }

            $validated = $request->validate([
                'role' => 'required|string|in:BACKEND,FRONTEND,MOBILE,DESIGN,TESTING'
            ]);

            $memberMembership = ProjectMembership::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->first();

            if (!$memberMembership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member not found'
                ], 404);
            }

            $memberMembership->role = $validated['role'];
            $memberMembership->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Member role updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update member role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
