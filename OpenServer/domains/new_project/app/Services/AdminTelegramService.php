<?php


namespace App\Services;


use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class AdminTelegramService
{
    protected $telegramUserService;
    protected $regularService;
    protected $subjectService;
    protected $testService;
    public function __construct( RegularService $regularService, SubjectService $subjectService, TelegramUserService $telegramUserService, TestService $testService)
    {
        $this->telegramUserService = $telegramUserService;
        $this->regularService = $regularService;
        $this->subjectService = $subjectService;
        $this->testService = $testService;
    }

    public function sendHello($user)
    {
        $name = $user->first_name != '@' ? $user->first_name : '';

        $text = "πSalom " . $name .  " ! \n \n π’\"ABACUS\" o'quv markazi botiga xush kelibsiz!!!";


        Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
        $this->sendHomeMarkup($user);

    }
    public function sendHomeMarkup($user)
    {
        $text ="Kerakli bo'limni tanlangπ";

        $keyboard = [
            ['π Yangi test joylashtirish', "π Test natijalarini ko'rish"],
            ["π¨βπ©βπ§βπ¦ Bot foydalanuvchilarini ko'rish", "π Fan qo'shish"]
        ];
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->telegramUserService->setUserStep($user, 1);

        Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup]);
    }

    public function selectCategory($user, $data)
    {
//        Log::info($this->regularService->checkSelectCategory($data, $user));
        if ($this->regularService->checkSelectCategory($data, $user)) {
            switch ($data['message']['text']) {
                case "π Fan qo'shish": {
                    $this->selectSubjectAdd($user);
                } break;
                case "π Yangi test joylashtirish": {
                    $this->selectTestAdd($user);
                } break;
                case "π¨βπ©βπ§βπ¦ Bot foydalanuvchilarini ko'rish": {
                    $this->selectUsersShow($user ,$data);
                } break;
            }

        } else {
            $this->sendHomeMarkup($user);
        }
    }
    public function selectSubjectAdd($user)
    {
        $text = "Fan nomini kiriting:";
        $keyboard = [
            ['π Asosiy menyuga']
        ];
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup]);
        $this->telegramUserService->setUserStep($user, 2);
    }

    public function getSubjectName($user, $data)
    {
        if($this->regularService->checkSubjectName($data, $user)) {
            $subject = $this->subjectService->create($data['message']['text']);
            if($subject) {
                $text = " <b>". $subject->name ."</b>  fani muvaffaqiyatli qo'shildiπ";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
                $this->telegramUserService->setUserStep($user, 1);
                $this->sendHomeMarkup($user);
            }
        }
    }

    public function selectTestAdd($user)
    {
        $text = "Fanni tanlang:";
        $subjects = $this->subjectService->all()->pluck('name');
        $keyboard = $subjects->chunk(3);
        $keyboard->push(['π Asosiy menyuga']);

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup]);
        $this->telegramUserService->setUserStep($user, 3);
    }

    public function selectSubject($user, $data)
    {
        if($this->regularService->checkSelectSubject($data, $user)) {
            if ($data['message']['text'] != 'π Asosiy menyuga') {

                $this->testService->create($data['message']['text']);

                $text = "Fan: <b>". $data['message']['text'] . "</b> \n \n \n Test javoblarini (,) bilan kiriting: \n Masalan:  <b>A,B,C,D ...</b>";
                $keyboard = [['β Bekor qilish']];

                $reply_markup = Keyboard::make([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ]);
                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup ]);
                $this->telegramUserService->setUserStep($user, 4);
            } else {
                $this->selectTestAdd($user);
            }
        }
    }
    public function getAnswers($user, $data)
    {
        if ($data['message']['text'] != 'β Bekor qilish') {
            if($this->regularService->checkAnswers($data, $user)) {

                $test_form = $this->testService->update( 'answers', $data['message']['text']);
                $answers = explode(',', $data['message']['text']);
                $text = "Fan: <b>". $test_form->subject->name . "</b> \n";
                foreach ($answers as $key => $answer) {
                    $text .= "<b>". ($key + 1) .") " . $answer . "</b>\n";
                }
                $text .= "\n \n Test yakunlanish muddatini (kk.oo.yyyy) formatda kiriting: \n Masalan:  <b>01.01.1991</b>";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
                $this->telegramUserService->setUserStep($user, 5);
            } else {
                $text = "β Noto'g'ri format \n \n Javoblarini (,) bilan kiriting: \n Masalan  <b>A,B,C,D ...</b>";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
            }
        } else {
            $this->testService->deleteLast();
            $this->sendHomeMarkup($user);
        }
    }
    public function getTestStopDate($user, $data)
    {
        if ($data['message']['text'] != 'β Bekor qilish') {
            if($this->regularService->checkDate($data, $user)) {

                $test_form = $this->testService->update( 'date_stop', $data['message']['text']);
                $answers = explode(',', $test_form->answers);
                $text = "Fan: <b>". $test_form->subject->name . "</b> \n";
                foreach ($answers as $key => $answer) {
                    $text .= "<b>". ($key + 1) .") " . $answer . "</b>\n";
                }
                $text .= "Test boshlanish muddati: <b>" . Carbon::parse($test_form->date_start)->format('d.m.Y') . "</b>\n";
                $text .= "Test yakunlanish muddati: <b>" . Carbon::parse($test_form->date_stop)->format('d.m.Y') . "</b> ";
                $text .= "\n \n Test faylini rasm ko'rinishida yuklang:";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text ]);
                $this->telegramUserService->setUserStep($user, 6);
            } else {
                $text = "β Noto'g'ri format \n \n Test yakunlanish muddatini (kk.oo.yyyy) formatda kiriting: \n Masalan:  <b>01.01.1991</b>";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
            }
        } else {
            $this->testService->deleteLast();
            $this->sendHomeMarkup($user);
        }
    }
    public function getTestFile($user, $data)
    {
        if (isset($data['message']['text']) && $data['message']['text'] == 'β Bekor qilish') {
            $this->testService->deleteLast();
            $this->sendHomeMarkup($user);
        } else if(isset($data['message']['text']) && $data['message']['text'] == 'β Saqlash') {
            $test = $this->testService->createTrue();
            $answers = explode(',', $test->answers);
            $text = "π Test muvaffaqiyatli yaratildi \n";
            $text .= "Fan: <b>". $test->subject->name . "</b> \n Javoblar: \n";
            foreach ($answers as $key => $answer) {
                $text .= "<b>". ($key + 1) .") " . $answer . "</b>\n";
            }
            $text .= "Test boshlanish muddati: <b>" . Carbon::parse($test->date_start)->format('d.m.Y') . "</b>\n";
            $text .= "Test yakunlanish muddati: <b>" . Carbon::parse($test->date_stop)->format('d.m.Y') . "</b> ";
            foreach (explode(',', $test->file_path) as $file_id) {
                Telegram::sendPhoto(['chat_id' => $user->chat_id, 'photo' => $file_id ]);
            }

            Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
            $this->sendHomeMarkup($user);
        } else {
            if($this->regularService->checkPhoto($data, $user)) {
                $test_form = $this->testService->storeFile($data['message']['photo']);

                $keyboard = [['β Saqlash', 'β Bekor qilish']];

                $reply_markup = Keyboard::make([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ]);
                $text = "β Rasm muvaffaqiyatli saqlandi! \n \n Yana rasm yuklang yoki <b> Saqlash</b> tugmasini bosingπ";
                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup ]);
            } else {
                $text = "β Noto'g'ri format \n \n Test faylini rasm shaklida yuklang!";

                Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text]);
            }
        }
    }
    public function selectUsersShow($user, $data)
    {
        $text = "<b> Foydalanuvchilar:</b> \n";
        $page = $this->telegramUserService->getPage($data);
        Log::info('page: '. $page);
        Log::info('show: '. $page != 'delete');
        if($page != 'delete') {
            $users_text = $this->telegramUserService->getUsers($page);
            $text .= $users_text;
            $keyboard = [[
                [ 'text' => 'β?', 'callback_data' => 'page_' . ($page ? $page - 1 : 0)  ],
                [ 'text' => 'β', 'callback_data' => 'page_delete' ],
                [ 'text' => 'β­', 'callback_data' => 'page_' . ($page + 1) ]
            ]];
            $reply_markup = Keyboard::make([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
            $response = Telegram::sendMessage(['chat_id' =>$user->chat_id, 'parse_mode'=>'html','text' => $text, 'reply_markup' => $reply_markup]);
            $this->telegramUserService->setUserStep($user, 7);
            $this->telegramUserService->setUserData($user, $response->getMessageId());
        } else {
            Telegram::deleteMessage(['chat_id' => $user->chat_id, 'message_id' => json_decode($user->data)->message_id]);
            $this->sendHomeMarkup($user);
        }
    }
}
