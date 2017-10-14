<?php

namespace Silvanite\Brandenburg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use App\User;

class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name'
    ];

    /**
     * The attributes which should be extended to the model
     *
     * @var array
     */
    protected $appends = [
        'permissions'
    ];

    /**
     * Get all users which are assigned a specific role
     *
     * @return Illuminate\Support\Collection
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Returns all Permissions for this Role
     *
     * @return Illuminate\Support\Collection
     */
    public function getPermissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Replace all existing permissions with a new set of permissions
     *
     * @param array $permissions
     * @return void
     */
    public function setPermissions(array $permissions)
    {
        $this->revokeAll();

        collect($permissions)->map(function ($permission) {
            $this->grant($permission);
        });
    }

    /**
     * Check if a user has a given permission
     *
     * @param string $permission
     * @return boolean
     */
    public function hasPermission($permission)
    {
        return (bool)Permission::where('role_id', $this->id)
                               ->where('permission_slug', $permission)->count();
    }

    /**
     * Give Permission to a Role
     *
     * @param string $permission
     * @return boolean
     */
    public function grant($permission)
    {
        if ($this->hasPermission($permission)) return true;

        if (!array_has(Gate::abilities(), $permission)) 
            abort(403, 'Unknown permission');

        return Permission::create([
            'role_id' => $this->id, 
            'permission_slug' => $permission
        ]);

        return false;
    }

    /**
     * Revokes a Permission from a Role
     *
     * @param string $permission
     * @return boolean
     */
    public function revoke($permission)
    {
        if (is_string($permission)) {
            return Permission::findOrFail($permission)->delete();
        }

        return false;
    }

    /**
     * Remove all permissions from this Role
     *
     * @return void
     */
    public function revokeAll()
    {
        return $this->getPermissions()->delete();
    }

    /**
     * Get a list of permissions
     *
     * @return array
     */
    public function getPermissionsAttribute()
    {
        return Permission::where('role_id', $this->id)->get()->pluck('permission_slug');
    }
}