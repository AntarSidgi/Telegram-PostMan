<?php
ob_start();
error_reporting(0);
define('API_KEY','BotToken');
//--------[Your Config]--------//
$Dev = admin id;
$Channel = '@Team_SD';
//-----------------------------//
//-----------------------------------------------------------------------------------------
function Antar($method,$datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}
function SendMessage($chat_id, $text, $mode, $reply, $keyboard = null){
	Antar('SendMessage',[
	'chat_id'=>$chat_id,
	'text'=>$text,
	'parse_mode'=>$mode,
	'reply_to_message_id'=>$reply,
	'reply_markup'=>$keyboard
	]);
	}
function Forward($chat_id,$from_id,$massege_id){
    Antar('ForwardMessage',[
    'chat_id'=>$chat_id,
    'from_chat_id'=>$from_id,
    'message_id'=>$massege_id
    ]);
    }
//-----------------------------------------------------------------------------------------------
$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$text = $message->text;
$from_id = $message->from->id;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
$data = $update->callback_query->data;
//-----KeyBoard
$menu = json_encode(['keyboard'=>[
[['text'=>"🔖 Send Post"]],
[['text'=>"About the bot ⚓️"],['text'=>"🚦 Contact support"]]
],'resize_keyboard'=>true]);
$back = json_encode(['keyboard'=>[
[['text'=>"▫️ Return ▫️"]]
],'resize_keyboard'=>true]);
$remove = json_encode(['KeyboardRemove'=>[
],'remove_keyboard'=>true]);

$panel = json_encode(['keyboard'=>[
[['text'=>"statistics"]],
[['text'=>"Public Forward"],['text'=>"Public Posting"]],
[['text'=>"Clear the blockers"],['text'=>"Empty queues"]],
[['text'=>"▫️ Return ▫️"]]
],'resize_keyboard'=>true]);
$back_panel = json_encode(['keyboard'=>[
[['text'=>"Return Back"]]
],'resize_keyboard'=>true]);
//-----------------------------------------------------------------------------------------------
$user = json_decode(file_get_contents("data/user.json"),true);
$all = $user['userlist'];
$users = json_decode(file_get_contents("data/$from_id.json"),true);
$step = $users['step'];
$post = $users['post'];
$stats = $users['stats'];
//===================================================================
if($update->message->chat->type == 'private')
if(!in_array($from_id, $user["userlist"]) == true) {
$user["userlist"][]="$from_id";
$user = json_encode($user,true);
file_put_contents("data/user.json",$user);
$users['step'] = "none";
$users['post'] = "";
$users['stats'] = "ok";
file_put_contents("data/$from_id.json",json_encode($users));
}
//===================================================================
$ban = json_decode(file_get_contents("data/banlist.json"),true);
$banlist = $ban['list'];
if(in_array($from_id, $banlist)){return;}
//-----------------------------------------------------------------------------------------------
if(preg_match("/^\/([Ss][Tt][Aa][Rr][Tt])$/",$text) || $text == "▫️ Return ▫️")
{
	$users['step'] = "none";
	file_put_contents("data/$from_id.json",json_encode($users));
    SendMessage($chat_id,"Welcome to Antar Postman bot :)
	With this bot you can send your useful and educational content in text format to be included in $Channel
	Note that in education, do not put your channel ID or ID in training
	The bot will automatically show the sender at the end of the post so you only post tutorials or posts
	➖➖➖➖➖➖
	🔻 Select 🔻", 'MarkDown' ,$message_id, $menu);
return;
}
elseif($text == "🔖 Send Post")
{
	if($stats == "ok")
	{
	$users['step'] = "post";
	file_put_contents("data/$from_id.json",json_encode($users));
	
	SendMessage($chat_id,"Post your post in text format to be included in the channel \nPlease note that your post must be educational.\n*Parse Mode : HTML*", 'MarkDown' ,$message_id, $back);
	}else{
	SendMessage($chat_id,"You have just posted a post \n😄 Wait for your previous post to be cleared then you will be able to post another one.", 'MarkDown' ,$message_id);
	}
}
elseif($text and $step == "post" and $stats == "ok")
{
	$users['step'] = "none";
	$users['post'] = "$text";
	$users['stats'] = "Waiting";
	file_put_contents("data/$from_id.json",json_encode($users));
	
	$mention = "<a href='tg://user?id=$from_id'>$from_id</a>";
	$confirm = json_encode(['inline_keyboard'=>[
    [['text'=>"Confirmation ✅",'callback_data'=>"accept|$from_id"],['text'=>"❌ Reject",'callback_data'=>"reject|$from_id"]],
    [['text'=>"Blocked ⛔️",'callback_data'=>"ban|$from_id"]]
    ]]);
	SendMessage($Dev,"$text", 'Html' ,null);
	SendMessage($Dev,"■ Post Text ☝🏻️ \nSending User: [$mention] \n \nSelect one of the following options:", 'Html' , null, $confirm);
	SendMessage($chat_id,"■ The post was successfully registered and will be placed on the channel after the admin has approved it 😬", 'MarkDown' ,$message_id, $menu);
}
if($data)
{
    $ex = explode('|', $data);
	$kar = $ex[0];
    $id = $ex[1];
	$karbar = json_decode(file_get_contents("data/$id.json"),true);
    $Post = $karbar['post'];
	$mention = "<a href='tg://user?id=$id'>$id</a>";
	
    switch ($kar)
	{
    case 'accept': Antar('answerCallbackQuery',['callback_query_id'=>$update->callback_query->id,'text'=>"The post was successfully approved and placed on the channel ✅",'show_alert'=>false]);
	SendMessage($id,"■ Your post has been approved by Admin and placed on the channel", 'Html' ,null);
    SendMessage($Channel,"$Post\n\n🔖 User posted: $mention\n➖➖➖➖➖➖➖\n@Team_SD", 'Html' ,null);
	$karbar['post'] = "";
	$karbar['stats'] = "ok";
	file_put_contents("data/$id.json",json_encode($karbar));
    break;
    case 'reject': Antar('answerCallbackQuery',['callback_query_id'=>$update->callback_query->id,'text'=>"Post rejected successfully ❌",'show_alert'=>false]);
    SendMessage($id,"■ Your post has been approved by Admin and placed on the channel", 'Html' ,null);
	$karbar['post'] = "";
	$karbar['stats'] = "ok";
	file_put_contents("data/$id.json",json_encode($karbar)); break;
    case 'ban': Antar('answerCallbackQuery',['callback_query_id'=>$update->callback_query->id,'text'=>"User successfully blocked from bot system ⛔️",'show_alert'=>false]);
	$ban['list'][] = "$id";
    $ban = json_encode($ban,true);
    file_put_contents("data/banlist.json",$ban);
	SendMessage($id,"■ You have been deprived of the entire post-bot system by admin", 'Html' ,null);
	break;
	}
}
elseif($text == "About the bot ⚓️")
{
	SendMessage($chat_id,"➖➖➖➖➖➖
🤖 Antar PostMan bot_

✍🏻 Programmer: @Team_SD

💠 bot Programming language : *PHP*

■ With this bot you will be able to post useful posts and content on programming or telegram bot in the channel.
➖➖➖➖➖➖", 'MarkDown' ,$message_id);
}
elseif($text == "🚦 Contact support")
{
	$users['step'] = "ticket";
	file_put_contents("data/$from_id.json",json_encode($users));
	SendMessage($chat_id,"_ [Message | Comment] _ Send in text format and wait for response from support ✔️\n➖➖➖➖", 'MarkDown' ,$message_id, $back);
}
elseif($step == "ticket")
{
Forward($Dev,$chat_id,$message_id);
$users['step'] = "none";
file_put_contents("data/$from_id.json",json_encode($users));
SendMessage($chat_id,"🍃 Your message will be sent to the support team and will respond as needed.", 'MarkDown' ,$message_id, $menu);
}
elseif($update->message->reply_to_message->forward_from->id != null and $from_id == $Dev){
SendMessage($update->message->reply_to_message->forward_from->id,"Message from team management 👇🏻", 'Html' ,null);
Forward($update->message->reply_to_message->forward_from->id,$Dev,$message_id);
SendMessage($chat_id,"■ Your message has been forwarded to the user", 'Html' ,$message_id);
}
if(!$text){SendMessage($chat_id,"😐", 'MarkDown' ,$message_id); return;}
//--------[Dev]--------//
if($from_id == $Dev){
if(preg_match("/^\/([Pp][Aa][Nn][Ee][Ll])$/",$text) || $text == "Return Back"){
	$users['step'] = "none";
	file_put_contents("data/$from_id.json",json_encode($users));
SendMessage($chat_id,"Welcome to the admin panel
➖➖➖➖➖➖
🔻 Select 🔻", 'MarkDown' ,$message_id, $panel);
return;
}
elseif($text == "statistics"){
	$mmemcount = count($all)-1;
    SendMessage($chat_id,"■ Total number of bot members : *$mmemcount*", 'MarkDown', $message_id);
}
elseif($text == "Clear the blockers"){
	unlink("data/banlist.json");
    SendMessage($chat_id,"■ All deprived people of the bot were released", 'MarkDown', $message_id);
}
elseif($text == "Empty queues"){
while($z <= count($all)){  
$z++;
$karbarha = json_decode(file_get_contents("data/".$all[$z-1].".json"),true);
$karbarha['stats'] = "ok";
file_put_contents("data/".$all[$z-1].".json",json_encode($karbarha));
}
SendMessage($chat_id,"■ All queues were empty", 'MarkDown', $message_id, $panel);
}
//------------------------------------------------------------------------------Send and For
elseif($text == "Public Posting"){
    $users['step'] = "s2all";
	file_put_contents("data/$from_id.json",json_encode($users));
    SendMessage($chat_id,"■ Send the message you want", 'MarkDown', $message_id, $back_panel);
}
elseif($step == "s2all"){
    $users['step'] = "none";
	file_put_contents("data/$from_id.json",json_encode($users));
while($z <= count($all)){  
$z++;
SendMessage($all[$z-1], $text, null, null);
}
SendMessage($chat_id,"■ The message was sent to all members", 'MarkDown', $message_id, $panel);
}
elseif($text == "Public Forward"){
    $users['step'] = "f2all";
	file_put_contents("data/$from_id.json",json_encode($users));
	SendMessage($chat_id,"■ Forward the message you want", 'MarkDown', $message_id, $back_panel);
}
elseif($step == "f2all"){
    $users['step'] = "none";
	file_put_contents("data/$from_id.json",json_encode($users));
while($z <= count($all)){  
$z++;
Forward($all[$z-1],$chat_id,$message_id);
}
SendMessage($chat_id,"■ The message was forwarded to all members", 'MarkDown', $message_id, $panel);
}

}
?>
