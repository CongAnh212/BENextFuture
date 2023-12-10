<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Connection;
use App\Models\Friend;
use App\Models\Group;
use App\Models\Notification;
use App\Models\RequestGroup;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function data_all_group(Request $request)
    {
        $client = $request->user();
        $group_participated = Group::join('connections', 'connections.id_group', 'groups.id')
            ->where('id_client', $client->id)
            ->select('groups.id')
            ->pluck('groups.id');
        $discover = Group::whereNotIn('id', $group_participated)
            ->where('display', Group::visible)
            ->get();
        return response()->json([
            'data' => $discover,
        ]);
    }
    public function data_your_group(Request $request)
    {
        // SELECT connections.* from groups
        // join connections on connections.id_group = groups.id
        // where id_client = 2
        $client = $request->user();
        $your_group = Group::join('connections', 'connections.id_group', 'groups.id')
            ->where('id_client', $client->id)
            ->where('id_role', Role::admin)
            ->select('groups.*')
            ->get();
        foreach ($your_group as $key => $value) {
            $getMember = Connection::where('id_group', $value->id)
                ->groupBy('id_group')
                ->select(DB::raw('count(*) as member'))
                ->first(); // Sử dụng first() để lấy một dòng duy nhất từ câu truy vấn
            $your_group[$key]->member = $getMember->member;
        }
        return response()->json([
            'data' => $your_group,
        ]);
    }
    public function data_group_participated(Request $request)
    {
        // SELECT  groups.* from groups
        // join connections on connections.id_group = groups.id
        // where id_client = 2
        $client = $request->user();
        $group_participated = Group::join('connections', 'connections.id_group', 'groups.id')
            ->where('id_client', $client->id)
            ->where('id_role', "!=", Role::admin)
            ->select('groups.*')
            ->get();
        foreach ($group_participated as $key => $value) {
            $getMember = Connection::where('id_group', $value->id)
                ->groupBy('id_group')
                ->select(DB::raw('count(*) as member'))
                ->first(); // Sử dụng first() để lấy một dòng duy nhất từ câu truy vấn
            $group_participated[$key]->member = $getMember->member;
        }
        return response()->json([
            'data' => $group_participated,
        ]);
    }
    public function dataAllGroupParticipated(Request $request)
    {
        $client = $request->user();
        $group_participated = Group::join('connections', 'connections.id_group', 'groups.id')
            ->where('id_client', $client->id)
            ->select('groups.*')
            ->get();
        foreach ($group_participated as $key => $value) {
            $getMember = Connection::where('id_group', $value->id)
                ->groupBy('id_group')
                ->select(DB::raw('count(*) as member'))
                ->first(); // Sử dụng first() để lấy một dòng duy nhất từ câu truy vấn
            $group_participated[$key]->member = $getMember->member;
        }
        return response()->json([
            'data' => $group_participated,
        ]);
    }

    public function createGroup(Request $request)
    {
        $client = $request->user();
        try {
            if ($client) {
                DB::beginTransaction();
                $create_group = Group::create([
                    'group_name' => $request->name_group,
                    'cover_image' => "cover/cover_image.png",
                    'privacy' => $request->privacy,
                    'display' => $request->display,
                ]);
                $connection = Connection::create([
                    'id_role' => Role::admin,
                    'id_client' => $client->id,
                    'id_group' => $create_group->id,
                ]);
                $id_invites = $request->id_invites;
                foreach ($id_invites as $key => $value) {
                    RequestGroup::create([
                        'id_client'     => $client->id,
                        'id_group'      => $create_group->id,
                        'id_invite'     => $value,
                        'status'        => RequestGroup::invite,
                    ]);
                }
                if ($connection) {
                    DB::commit();
                    return response()->json([
                        'status'    => 1,
                        'id_group'   => $create_group->id,
                    ]);
                }
            } else {
                return response()->json([
                    'status'    => 0,
                    'message'   => 'Create group erorr',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function dataInvite(Request $request)
    {
        $search = '%' . $request->value . "%";
        $client = $request->user();
        $friends = Friend::select('clients.*')
            ->from(function ($query) use ($client) {
                $query->select('id_friend as result_id')
                    ->from('friends')
                    ->where('my_id', $client->id)
                    ->union(
                        DB::table('friends')
                            ->select('my_id as result_id')
                            ->where('id_friend', $client->id)
                    );
            }, 'new')
            ->join('clients', 'clients.id', '=', 'new.result_id')
            ->where('clients.fullname', 'like', $search)
            ->whereNotIn('clients.id', $request->id_invites)
            ->get();

        return response()->json([
            'friends'    => $friends,
            'ids' => $request->all()
        ]);
    }

    public function infoGroup($id_group)
    {
        $info = Group::find($id_group);
        $member = Connection::where('id_group', $info->id)->select('id_client')->pluck('id_client');
        $info->member = $member->count();
        $info_members = Client::whereIn('id', $member)->inRandomOrder()->limit(3)->get();
        return response()->json([
            'info'    => $info,
            'member'    => $info_members
        ]);
    }
    public function dataInviteDetail(Request $request)
    {
        $inGroup = Connection::where('id_group', $request->id_group)->pluck('id_client');
        $client = $request->user();

        $friend = Friend::where('my_id', $client->id)
            ->select('id_friend as result_id')
            ->union(
                Friend::where('id_friend', $client->id)
                    ->select('my_id as result_id')
            )
            ->get();
        $data = Client::whereIn('id', $friend)->whereNotIn('id', $inGroup)->get();

        return response()->json([
            'data'    => $data,
        ]);
    }
    public function sendInvite(Request $request)
    {
        try {
            DB::beginTransaction();
            $client = $request->user();
            foreach ($request->id_invites as $key => $value) {
                $check = RequestGroup::where('id_invite', $value['id'])
                    ->where('id_group', $request->id_group)->first();
                if ($check) {
                    $check->update([
                        'created_at' => Carbon::now(),
                    ]);
                    Notification::where('id_client', $value['id'])
                        ->where('id_group', $request->id_group)
                        ->where('my_id', $client->id)
                        ->update([
                            'created_at' => Carbon::now(),
                        ]);
                } else {
                    RequestGroup::create([
                        'id_client'     => $client->id,
                        'id_group'      => $request->id_group,
                        'id_invite'     => $value['id'],
                        'status'        => RequestGroup::invite,
                    ]);
                    Notification::create([
                        'id_client'     => $value['id'],
                        'my_id'         => $client->id,
                        'id_group'      => $request->id_group,
                        'type'          => Notification::invite_group,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status'    => 1,
                'message'   => 'invitation sent successfully!',
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function comeInGroup(Request $request)
    {
        try {
            DB::beginTransaction();
            $client = $request->user();
            $check = RequestGroup::where('id_group', $request->id)->where('id_invite', $client->id)->first();
            if (!$check) {
                RequestGroup::create([
                    'id_group' => $request->id,
                    'id_invite' => $client->id,
                    'status' => RequestGroup::come,
                ]);
            }

            DB::commit();
            return response()->json([
                'status'    => 1,
                'message'   => 'You have successfully sent your request!',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => 'Send request failed!',
            ]);
        }
    }
    public function getData(Request $request)
    {
        $data = Group::find($request->id_group);
        return response()->json([
            'data'    => $data,
        ]);
    }
    public function updatePrivacy(Request $request)
    {
        try {
            DB::beginTransaction();
            $group = Group::find($request->id_group);
            $privacy = $group->privacy != Group::public ? Group::public : Group::private;
            Group::find($request->id_group)->update([
                'privacy' => $privacy
            ]);
            DB::commit();
            if ($privacy != Group::public) {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'This group will be private from now on',
                ]);
            } else {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'This group will be public from now on',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function updateDisplay(Request $request)
    {
        try {
            DB::beginTransaction();
            $group = Group::find($request->id_group);
            $display = $group->display  != Group::visible ? Group::visible : Group::hidden;

            Group::find($request->id_group)->update([
                'display' => $display
            ]);
            DB::commit();
            if ($display == Group::visible)
                $mess = "";

            return response()->json([
                'status'    => 1,
                'message'   => 'oke',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function updateJoinApproval(Request $request)
    {
        try {
            DB::beginTransaction();
            $group = Group::find($request->id_group);
            $group->update([
                'join_approval' => $request->join_approval
            ]);
            DB::commit();


            if ($request->join_approval == Group::turnOnJoin) {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'From now on, applications to join the group need to be approved!',
                ]);
            } else {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'From now on you can directly join the group!',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function updatePostApproval(Request $request)
    {

        try {
            DB::beginTransaction();
            $group = Group::find($request->id_group);
            $group->update([
                'post_approval' => $request->post_approval
            ]);

            DB::commit();
            if ($request->post_approval == true) {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'From now on, applications to post in the group need to be approved!',
                ]);
            } else {
                return response()->json([
                    'status'    => 1,
                    'message'   => 'From now on you can directly post in the group!',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => $th,
            ]);
        }
    }
    public function dataInvited(Request $request)
    {
        $client = $request->user();
        $data_invited = DB::table(DB::raw('(select rg.id_client, rg.status, rg.id_group, c.fullname, rg.id_invite, rg.created_at
                                            from request_groups rg
                                            left join clients c on c.id = rg.id_invite) as a'))
            ->select('a.*', 'clients.fullname', 'groups.group_name', 'groups.cover_image')
            ->leftJoin('clients', 'clients.id', '=', 'a.id_client')
            ->leftJoin('groups', 'groups.id', '=', 'a.id_group')
            ->where('a.status', '=', RequestGroup::invite)
            ->where('id_invite', $client->id)
            ->orderByDesc('a.created_at')
            ->get();

        return response()->json([
            'status'    => 1,
            'data_invited'      => $data_invited,
        ]);
    }
}
