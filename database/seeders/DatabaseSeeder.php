<?php
namespace Database\Seeders;
use App\Domain\Shared\Role;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('The SentinelOps demo seeder is disabled in production.');
        }

        $org=Organization::firstOrCreate(['slug'=>'sentinel-demo'],['public_id'=>(string)Str::uuid(),'name'=>'SentinelOps Demo Operations']);
        $roles=[
            ['Administrator','admin@sentinelops.test',Role::Administrator],
            ['Supervisor Delta','supervisor@sentinelops.test',Role::Supervisor],
            ['Field Operator Bravo','technician@sentinelops.test',Role::Technician],
            ['Security Officer','security@sentinelops.test',Role::SecurityOfficer],
            ['Audit Reviewer','auditor@sentinelops.test',Role::Auditor],
        ];
        $users=[];
        foreach($roles as [$name,$email,$role]){
            $user=User::firstOrCreate(['email'=>$email],['name'=>$name,'password'=>Hash::make('SentinelOps!2026')]);
            OrganizationMembership::updateOrCreate(['organization_id'=>$org->id,'user_id'=>$user->id],['role'=>$role->value,'is_active'=>true]);
            $users[$role->value]=$user;
        }
        $site=Location::firstOrCreate(['organization_id'=>$org->id,'code'=>'SITE-B'],['name'=>'Site B']);
        $asset=Asset::firstOrCreate(['organization_id'=>$org->id,'code'=>'GENERATOR-0041'],[
            'public_id'=>(string)Str::uuid(),'location_id'=>$site->id,'name'=>'Mobile Generator 41','status'=>'deployed','revision'=>2,
        ]);
        AssetAssignment::firstOrCreate(['asset_id'=>$asset->id,'ended_at'=>null],[
            'organization_id'=>$org->id,'assignee_user_id'=>$users[Role::Technician->value]->id,'assigned_by_user_id'=>$users[Role::Supervisor->value]->id,'reason'=>'Field Team Bravo deployment','started_at'=>now(),
        ]);
    }
}
