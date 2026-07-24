<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['owner' => 'Owner', 'manager' => 'Manager', 'reception' => 'Reception', 'cashier' => 'Cashier', 'marketing' => 'Marketing'] as $name => $displayName) {
            Role::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }
        $owner=Role::where('name','owner')->firstOrFail();
        Admin::updateOrCreate(['email'=>'owner@smartcut.local'],['role_id'=>$owner->id,'name'=>'SM HAIR DESIGN Owner','password'=>Hash::make('ChangeMe123!'),'is_active'=>true]);
        $branch=Branch::firstOrCreate(['name'=>'SM HAIR DESIGN — Central'],['address'=>'Central กรุงเทพฯ','phone'=>'020000000','is_active'=>true]);
        Employee::firstOrCreate(['branch_id'=>$branch->id,'name'=>'ช่าง Alex'],['phone'=>'0800000000','is_active'=>true]);
        Service::firstOrCreate(['name'=>'Haircut Signature'],['duration_minutes'=>60,'price'=>500,'is_active'=>true]);
    }
}
