<?php

namespace App\Console\Commands;

use App\Models\Mailinglist;
use Illuminate\Console\Command;
use Infomaniak\ClientApiNewsletter\Client;

class CreateMailingList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:mailinglist {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a mailing list with a given name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $client = new Client(config('services.infomaniak.api'), config('services.infomaniak.secret'));

        $response = $client->post(Client::MAILINGLIST, [
            'params' => [
                'name' => $name,
            ],
        ]);

        if ($response->success()) {
            $mailingList = MailingList::create([
                'infomaniak_id' => $response->datas()['id'],
                'name' => $name,
            ]);

            $this->info('MailingList created successfully, ID: ' . $mailingList->id);
        } else {
            $this->error('Failed to create Mailing list: ' . print_r($response));
        }
    }
}
