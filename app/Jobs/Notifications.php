<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\Notifications as EventNotifications;
use App\Events\Message;
use App\Events\MessageGroup;
use App\Events\SystemNotification;
use App\Events\Orders;
use App\Events\Call;
use Pusher\Pusher;
class Notifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $type;
    public $data;
    public $pusher;
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
        // if(env('PUSHER_TYPE') != 'self'){
        //     $options = array(
        //         'cluster' => env('OTHER_PUSHER_CLUSTER'),
        //         'useTLS' => true
        //     );
        //     $this->pusher = new Pusher(
        //         env('OTHER_PUSHER_APP_KEY'),
        //         env('OTHER_PUSHER_APP_SECRET'),
        //         env('OTHER_PUSHER_APP_ID'),
        //         $options
        //     );
        // }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->type) {
            case 'message_group':
                broadcast(new MessageGroup($this->data));
                break;
            case 'notifications':
                broadcast(new EventNotifications($this->data));
                break;
            case 'message':
                broadcast(new Message($this->data));
                break;
            case 'system_notification':
                broadcast(new SystemNotification($this->data));
                break;
            case 'orders':
                broadcast(new Orders($this->data));
                break;
            case 'call':
                broadcast(new Call($this->data));
              break;
            default:
                # code...
                break;
        }
    }
}
