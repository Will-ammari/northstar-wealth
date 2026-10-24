<?php
namespace Database\Seeders;
use App\Models\Portfolio;
use App\Models\User;
use App\Actions\CreateAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!App::environment(['local','testing'])) { throw new \RuntimeException('Demo seeder may only run locally.'); }
        $users=[];
        foreach (['owner'=>'Olivia Morgan','editor'=>'Ethan Park','viewer'=>'Vera Chen','outsider'=>'Owen Reed'] as $role=>$name) {
            $users[$role]=User::firstOrCreate(['email'=>$role.'@example.test'],['name'=>$name,'password'=>'DemoPass123!']);
        }
        $portfolio=Portfolio::firstOrCreate(['name'=>'Northstar Family Office'],['created_by'=>$users['owner']->id,'base_currency'=>'USD']);
        foreach (['owner','editor','viewer'] as $role) {
            $portfolio->members()->syncWithoutDetaching([$users[$role]->id=>['role'=>$role]]);
        }
        if ($portfolio->assets()->exists()) { return; }
        $action=app(CreateAsset::class);
        foreach ([
            ['type'=>'stock','name'=>'Apple Inc.','symbol'=>'AAPL','quantity'=>'125','unit_price'=>'234.50'],
            ['type'=>'stock','name'=>'Vanguard S&P 500 ETF','symbol'=>'VOO','quantity'=>'80','unit_price'=>'535.20'],
            ['type'=>'crypto','name'=>'Bitcoin','symbol'=>'BTC','quantity'=>'1.25000000','unit_price'=>'68450.00'],
            ['type'=>'crypto','name'=>'Ethereum','symbol'=>'ETH','quantity'=>'12','unit_price'=>'2825.00'],
            ['type'=>'real_estate','name'=>'Westlake Apartment','symbol'=>null,'quantity'=>'1','unit_price'=>'420000.00'],
            ['type'=>'cash','name'=>'USD Savings','symbol'=>'USD','quantity'=>'75000','unit_price'=>'1'],
        ] as $data) { $action->execute($portfolio,$users['owner'],$data); }
        $other=Portfolio::firstOrCreate(['name'=>'Restricted Portfolio'],['created_by'=>$users['outsider']->id,'base_currency'=>'USD']);
        $other->members()->syncWithoutDetaching([$users['outsider']->id=>['role'=>'owner']]);
    }
}
