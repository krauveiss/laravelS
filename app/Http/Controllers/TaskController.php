<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function store(Request $request, string $projectId): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_task_id' => 'nullable|uuid|exists:tasks,id',
                'deadline' => 'nullable|date',
                'owner_id' => 'nullable|uuid|exists:users,id',
            ]);

            if (isset($validated['parent_task_id'])) {
                $parentTask = Task::find($validated['parent_task_id']);
                if ($parentTask && $parentTask->project_id !== $projectId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Parent task does not belong to this project'
                    ], 400);
                }
            }

            if (isset($validated['owner_id'])) {
                $ownerMembership = ProjectMembership::where('user_id', $validated['owner_id'])
                    ->where('project_id', $projectId)
                    ->first();

                if (!$ownerMembership) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Assigned user is not a member of this project'
                    ], 400);
                }
            }

            $task = Task::create([
                'id' => Str::uuid(),
                'project_id' => $projectId,
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'owner_id' => $validated['owner_id'] ?? $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'status' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(string $projectId): JsonResponse
    {
        try {
            $user = request()->user();

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $tasks = Task::where('project_id', $projectId)
                ->with(['owner', 'subtasks'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $task = Task::with(['owner', 'parent', 'project'])
                ->find($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $task->project_id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $task = Task::find($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $task->project_id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|integer|in:0,1,2',
                'deadline' => 'nullable|date',
                'owner_email' => 'nullable|email|exists:users,email',
            ]);

            if (isset($validated['owner_email'])) {
                $newOwner = User::where('email', $validated['owner_email'])->first();

                $ownerMembership = ProjectMembership::where('user_id', $newOwner->id)
                    ->where('project_id', $task->project_id)
                    ->first();

                if (!$ownerMembership) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Assigned user is not a member of this project'
                    ], 400);
                }

                $task->owner_id = $newOwner->id;
                unset($validated['owner_email']);
            }

            $task->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $task = Task::find($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $task->project_id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            if ($task->owner_id !== $user->id && !$membership->is_owner) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only task owner or project owner can delete this task'
                ], 403);
            }

            $task->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function subtasks(string $id): JsonResponse
    {
        try {
            $user = request()->user();

            $task = Task::find($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }

            $membership = ProjectMembership::where('user_id', $user->id)
                ->where('project_id', $task->project_id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this project'
                ], 403);
            }

            $subtasks = Task::where('parent_task_id', $id)
                ->with(['owner'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $subtasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get subtasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
