<?php
require 'TelegramException.php';

class telegramBot
{
    const BASE_URL = 'https://api.telegram.org/bot';
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
        if (is_null($this->token))
            throw new TelegramException('Required "token" key not supplied');
        $this->baseURL = self::BASE_URL . $this->token . '/';
        $this->lastUrl = "";
        $this->lastResponse = array();
        $this->triggerHttp = null;
    }

    public function getLastUrl()
    {
        return $this->lastUrl;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function setTriggerHttp($function)
    {
        return $this->triggerHttp = $function;
    }

    /**
     * Use this method to receive incoming updates using long polling.
     *
     * @link https://core.telegram.org/bots/api#getupdates
     *
     * @param int $offset
     * @param int $timeout
     * @param int $limit
     * @param mixed[] $allowed_updates
     *
     * @return mixed[]
     */
    public function getUpdates($offset = null, $timeout = null, $limit = null, $allowed_updates = null)
    {
        $params = compact('offset', 'limit', 'timeout', 'allowed_updates');
        return $this->sendRequest('getUpdates', $params);
    }

    /**
     * Use this method to specify a url and receive incoming updates via an outgoing webhook. Whenever there is an
     * update for the bot, we will send an HTTPS POST request to the specified url, containing a JSON-serialized
     * Update.
     *
     * @link https://core.telegram.org/bots/api#setwebhook
     *
     * @param string $url
     * @param mixed $certificate
     * @param int $max_connections
     * @param mixed[] $allowed_updates
     *
     * @return mixed[]
     *
     * @throws TelegramException
     */
    public function setWebhook($url = null, $certificate = null, $max_connections = 40, $allowed_updates = null)
    {
        if (is_null($url)) {
            return $this->deleteWebhook();
        } else {
            if (filter_var($url, FILTER_VALIDATE_URL) === false)
                throw new TelegramException('Invalid URL provided');
            if (parse_url($url, PHP_URL_SCHEME) !== 'https')
                throw new TelegramException('Invalid URL, it should be a HTTPS url.');
            if (is_null($certificate))
                return $this->sendRequest('setWebhook', compact('url', 'certificate', 'max_connections', 'allowed_updates'));
            else
                return $this->uploadFile('setWebhook', compact('url', 'certificate', 'max_connections', 'allowed_updates'));
        }
    }

    /**
     * Use this method to remove webhook integration if you decide to switch back to getUpdates. Returns True on success. Requires no parameters.
     *
     * @link https://core.telegram.org/bots/api#deletewebhook
     *
     * @return mixed[]
     */
    public function deleteWebhook()
    {
        return $this->sendRequest('deleteWebhook', array());
    }

    /**
     * Use this method to get current webhook status. Requires no parameters. On success, returns a WebhookInfo object.
     * If the bot is using getUpdates, will return an object with the url field empty.
     *
     * @return mixed[]
     * @link Use this method to get current webhook status. Requires no parameters. On success, returns a WebhookInfo
     * object. If the bot is using getUpdates, will return an object with the url field empty.
     *
     */
    public function getWebhookInfo()
    {
        return $this->sendRequest('getWebhookInfo', array());
    }

    /**
     * A simple method for testing your bot's auth token.
     * Returns basic information about the bot in form of a User object.
     *
     * @link https://core.telegram.org/bots/api#getme
     *
     * @return mixed[]
     */
    public function getMe()
    {
        return $this->sendRequest('getMe', array());
    }

    /**
     * Send text messages.
     *
     * @link https://core.telegram.org/bots/api#sendmessage
     *
     * @param int $chat_id
     * @param string $text
     * @param string $parse_mode
     * @param bool $disable_web_page_preview
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param InlineKeyboardMarkup|ReplyKeyboardMarkup|ReplyKeyboardRemove|ForceReply $reply_markup
     *
     * @return mixed[]
     */
    public function sendMessage($chat_id, $text, $parse_mode = null, $disable_web_page_preview = false, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'text', 'parse_mode', 'disable_web_page_preview', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendMessage', $params);
    }

    /**
     * Use this method to forward messages of any kind
     *
     * @link https://core.telegram.org/bots/api#forwardmessage
     *
     * @param int $chat_id
     * @param int $from_chat_id
     * @param int $message_id
     * @param bool $disable_notification
     *
     * @return mixed[]
     */
    public function forwardMessage($chat_id, $from_chat_id, $message_id, $disable_notification = false)
    {
        $params = compact('chat_id', 'from_chat_id', 'message_id', 'disable_notification');
        return $this->sendRequest('forwardMessage', $params);
    }

    /**
     * Use this method to send photos.
     *
     * @link https://core.telegram.org/bots/api#sendphoto
     *
     * @param int $chat_id
     * @param string $photo
     * @param string $caption
     * @param string $parse_mode
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendPhoto($chat_id, $photo, $caption = null, $parse_mode = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'photo', 'caption', 'parse_mode', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($photo) || filter_var($photo, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendPhoto', $data);
        return $this->uploadFile('sendPhoto', $data);
    }

    /**
     * Use this method to send audio files, if you want Telegram clients to display them in the music player.
     * Your audio must be in the .MP3 or .M4A format.
     *
     * @link https://core.telegram.org/bots/api#sendaudio
     *
     * @param int $chat_id
     * @param string $audio
     * @param string $caption
     * @param string $parse_mode
     * @param int $duration
     * @param string $performer
     * @param string $title
     * @param mixed $thumb
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendAudio($chat_id, $audio, $caption = null, $parse_mode = null, $duration = null, $performer = null, $title = null, $thumb = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'audio', 'caption', 'parse_mode', 'duration', 'performer', 'title', 'thumb', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($audio) || filter_var($audio, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendAudio', $data);
        return $this->uploadFile('sendAudio', $data);
    }

    /**
     * Use this method to send general files.
     *
     * @link https://core.telegram.org/bots/api#senddocument
     *
     * @param int $chat_id
     * @param string $document
     * @param null $thumb
     * @param string $caption
     * @param null $parse_mode
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendDocument($chat_id, $document, $thumb = null, $caption = null, $parse_mode = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'document', 'thumb', 'caption', 'parse_mode', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($document) || filter_var($document, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendDocument', $data);
        return $this->uploadFile('sendDocument', $data);
    }

    /**
     * Use this method to send video files, Telegram clients support mp4 videos (other formats may be sent as Document).
     *
     * @link https://core.telegram.org/bots/api#sendvideo
     *
     * @param int $chat_id
     * @param string $video
     * @param int $duration
     * @param int $width
     * @param int $height
     * @param string $thumb
     * @param string $caption
     * @param null $parse_mode
     * @param null $supports_streaming
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendVideo($chat_id, $video, $duration = null, $width = null, $height = null, $thumb = null, $caption = null, $parse_mode = null, $supports_streaming = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'video', 'duration', 'width', 'height', 'thumb', 'caption', 'parse_mode', 'supports_streaming', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($video) || filter_var($video, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendVideo', $data);
        return $this->uploadFile('sendVideo', $data);
    }

    /**
     * Use this method to send animation files (GIF or H.264/MPEG-4 AVC video without sound).
     *
     * @link https://core.telegram.org/bots/api#sendanimation
     *
     * @param int $chat_id
     * @param string $animation
     * @param int $duration
     * @param int $width
     * @param int $height
     * @param null $thumb
     * @param string $caption
     * @param null $parse_mode
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendAnimation($chat_id, $animation, $duration = null, $width = null, $height = null, $thumb = null, $caption = null, $parse_mode = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'animation', 'duration', 'width', 'height', 'thumb', 'caption', 'parse_mode', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($animation) || filter_var($animation, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendAnimation', $data);
        return $this->uploadFile('sendAnimation', $data);
    }

    /**
     * Use this method to send audio files, if you want Telegram clients to display the file as a playable voice
     * message. For this to work, your audio must be in an .ogg file encoded with OPUS (other formats may be sent as
     * Audio or Document).
     *
     * @link https://core.telegram.org/bots/api#sendvoice
     *
     * @param int $chat_id
     * @param string $voice
     * @param string $caption
     * @param null $parse_mode
     * @param int $duration
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendVoice($chat_id, $voice, $caption = null, $parse_mode = null, $duration = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'voice', 'caption', 'parse_mode', 'duration', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($voice) || filter_var($voice, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendVoice', $data);
        return $this->uploadFile('sendVoice', $data);
    }

    /**
     * As of v.4.0, Telegram clients support rounded square mp4 videos of up to 1 minute long. Use this method to send
     * video messages.
     *
     * @link https://core.telegram.org/bots/api#sendvideonote
     *
     * @param int $chat_id
     * @param string $video_note
     * @param int $duration
     * @param int $length
     * @param null $thumb
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param array $reply_markup
     *
     * @return mixed[]
     *
     * @throws TelegramException
     */
    public function sendVideoNote($chat_id, $video_note, $duration = null, $length = null, $thumb = null, $disable_notification = null, $reply_to_message_id = null, $reply_markup = null)
    {
        $data = compact('chat_id', 'video_note', 'duration', 'length', 'thumb', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        if (!file_exists($video_note))
            return $this->sendRequest('sendVideoNote', $data);
        if (filter_var($video_note, FILTER_VALIDATE_URL))
            throw new TelegramException("Currently passing URL in sendVideoNote is not supported");
        return $this->uploadFile('sendVideoNote', $data);
    }

    /**
     * Use this method to send a group of photos or videos as an album.
     *
     * @link https://core.telegram.org/bots/api#sendmediagroup
     *
     * @param int $chat_id
     * @param mixed[] $media
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     *
     * @return mixed[]
     *
     */
    public function sendMediaGroup($chat_id, $media, $disable_notification = false, $reply_to_message_id = null)
    {
        $data = compact('chat_id', 'media', 'disable_notification', 'reply_to_message_id');
        if (!file_exists($media) || filter_var($media, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendMediaGroup', $data);
        return $this->uploadFile('sendMediaGroup', $data);
    }


    /**
     * Use this method to send point on the map.
     *
     * @link https://core.telegram.org/bots/api#sendlocation
     *
     * @param int $chat_id
     * @param float $latitude
     * @param float $longitude
     * @param int $live_period
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     * @param bool $disable_notification
     *
     * @return mixed[]
     * @throws TelegramException
     */
    public function sendLocation($chat_id, $latitude, $longitude, $live_period = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        if ($live_period !== null && ($live_period < 60 || $live_period > 86400))
            throw new TelegramException("Live Period Must be between 60 and 86400 seconds");
        $params = compact('chat_id', 'latitude', 'longitude', 'live_period', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendLocation', $params);
    }

    /**
     * Use this method to edit live location messages. A location can be edited until its live_period expires or editing
     * is explicitly disabled by a call to stopMessageLiveLocation.
     *
     * @link https://core.telegram.org/bots/api#editmessagelivelocation
     *
     * @param int $chat_id
     * @param int $message_id
     * @param int $inline_message_id
     * @param float $latitude
     * @param float $longitude
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function editMessageLiveLocation($chat_id = null, $message_id = null, $inline_message_id = null, $latitude = null, $longitude = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'latitude', 'longitude', 'reply_markup');
        return $this->sendRequest('editMessageLiveLocation', $params);
    }

    /**
     * Use this method to stop updating a live location message before live_period expires.
     *
     * @link https://core.telegram.org/bots/api#stopmessagelivelocation
     *
     * @param int $chat_id
     * @param int $message_id
     * @param int $inline_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function stopMessageLiveLocation($chat_id = null, $message_id = null, $inline_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'reply_markup');
        return $this->sendRequest('stopMessageLiveLocation', $params);
    }

    /**
     *Send Venue
     *
     * @link https://core.telegram.org/bots/api#sendvenue
     *
     * @param int $chat_id
     * @param float $latitude
     * @param float $longitude
     * @param string $title
     * @param string $address
     * @param string $foursquare_id
     * @param null $foursquare_type
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendVenue($chat_id, $latitude, $longitude, $title, $address, $foursquare_id = null, $foursquare_type = null, $disable_notification = null, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'latitude', 'longitude', 'title', 'address', 'foursquare_id', 'foursquare_type', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendVenue', $params);
    }

    /**
     *Send Contact
     *
     * @link https://core.telegram.org/bots/api#sendcontact
     *
     * @param int $chat_id
     * @param string $phone_number
     * @param string $first_name
     * @param string $last_name
     * @param null $vcard
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     *
     * @return mixed[]
     */
    public function sendContact($chat_id, $phone_number, $first_name, $last_name = null, $vcard = null, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'phone_number', 'first_name', 'last_name', 'vcard', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendContact', $params);
    }

    /**
     * Use this method to send a native poll.
     *
     * @link https://core.telegram.org/bots/api#sendpoll
     *
     * @param $chat_id
     * @param $question
     * @param $options
     * @param bool $is_anonymous
     * @param null $type
     * @param null $allows_multiple_answers
     * @param null $correct_option_id
     * @param bool $is_closed
     * @param bool $disable_notification
     * @param null $reply_to_message_id
     * @param null $reply_markup
     *
     * @return array|mixed
     */
    public function sendPoll($chat_id, $question, $options, $is_anonymous = false, $type = null, $allows_multiple_answers = null, $correct_option_id = null, $is_closed = false, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'question', 'options', 'is_anonymous', 'type', 'allows_multiple_answers', 'correct_option_id', 'is_closed', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendPoll', $params);
    }

    /**
     * Send Chat Action.
     *
     * @link https://core.telegram.org/bots/api#sendchataction
     *
     * @param int $chat_id
     * @param string $action
     *
     * @return mixed[]
     * @throws TelegramException
     */
    public function sendChatAction($chat_id, $action)
    {
        $actions = array(
            'typing',
            'upload_photo',
            'record_video',
            'upload_video',
            'record_audio',
            'upload_audio',
            'upload_document',
            'find_location',
            'record_video_note',
            'upload_video_note'
        );
        if (isset($action) && in_array($action, $actions)) {
            $params = compact('chat_id', 'action');
            return $this->sendRequest('sendChatAction', $params);
        }
        throw new TelegramException('Invalid Action! Accepted value: ' . implode(', ', $actions));
    }

    /**
     * Use this method to get a list of profile pictures for a user.
     *
     * @link https://core.telegram.org/bots/api#getuserprofilephotos
     *
     * @param int $user_id
     * @param int $offset
     * @param int $limit
     *
     * @return mixed[]
     */
    public function getUserProfilePhotos($user_id, $offset = null, $limit = null)
    {
        $params = compact('user_id', 'offset', 'limit');
        return $this->sendRequest('getUserProfilePhotos', $params);
    }

    /**
     * Use this method to get basic info about a file and prepare it for downloading. For the moment, bots can download
     * files of up to 20MB in size.
     *
     * @link https://core.telegram.org/bots/api#getfile
     *
     * @param String $file_id
     *
     * @return mixed[]
     */
    public function getFile($file_id)
    {
        return $this->sendRequest('getFile', compact('file_id'));
    }

    /**
     * Use this method to kick a user from a group, a supergroup or a channel. In the case of supergroups and channels,
     * the user will not be able to return to the group on their own using invite links, etc., unless unbanned first.
     * The bot must be an administrator in the chat for this to work and must have the appropriate admin rights.
     *
     * @link https://core.telegram.org/bots/api#kickchatmember
     *
     * @param int $chat_id
     * @param int $user_id
     * @param null $until_date
     *
     * @return mixed[]
     */
    public function kickChatMember($chat_id, $user_id, $until_date = null)
    {
        $params = compact('chat_id', 'user_id', 'until_date');
        return $this->sendRequest('kickChatMember', $params);
    }

    /**
     * Use this method to unban a previously kicked user in a supergroup or channel. The user will not return to the
     * group or channel automatically, but will be able to join via link, etc. The bot must be an administrator for this
     * to work.
     *
     * @param int $chat_id
     * @param int $user_id
     *
     * @return mixed[]
     */
    public function unbanChatMember($chat_id, $user_id)
    {
        $params = compact('chat_id', 'user_id');
        return $this->sendRequest('unbanChatMember', $params);
    }

    /**
     * Use this method to restrict a user in a supergroup. The bot must be an administrator in the supergroup for this
     * to work and must have the appropriate admin rights. Pass True for all permissions to lift restrictions from
     * a user.
     *
     * @link https://core.telegram.org/bots/api#restrictchatmember
     *
     * @param $chat_id
     * @param $user_id
     * @param $permissions
     * @param null $until_date
     *
     * @return array|mixed
     */
    public function restrictChatMember($chat_id, $user_id, $permissions, $until_date = null)
    {
        $params = compact('chat_id', 'user_id', 'permissions', 'until_date');
        return $this->sendRequest('restrictChatMember', $params);
    }

    /**
     * Use this method to promote or demote a user in a supergroup or a channel. The bot must be an administrator in
     * the chat for this to work and must have the appropriate admin rights. Pass False for all boolean parameters to
     * demote a user.
     *
     * @link https://core.telegram.org/bots/api#promotechatmember
     *
     * @param $chat_id
     * @param $user_id
     * @param null $can_change_info
     * @param null $can_post_messages
     * @param null $can_edit_messages
     * @param null $can_delete_messages
     * @param null $can_invite_users
     * @param null $can_restrict_members
     * @param null $can_pin_messages
     * @param null $can_promote_members
     *
     * @return array|mixed
     */
    public function promoteChatMember($chat_id, $user_id, $can_change_info = null, $can_post_messages = null, $can_edit_messages = null, $can_delete_messages = null, $can_invite_users = null, $can_restrict_members = null, $can_pin_messages = null, $can_promote_members = null)
    {
        $params = compact('chat_id', 'user_id', 'can_change_info', 'can_post_messages', 'can_edit_messages', 'can_delete_messages', 'can_invite_users', 'can_restrict_members', 'can_pin_messages', 'can_promote_members');
        return $this->sendRequest('promoteChatMember', $params);
    }

    /**
     * Send Sticker.
     *
     * @link https://core.telegram.org/bots/api#sendsticker
     *
     * @param int $chat_id
     * @param string $sticker
     * @param int $reply_to_message_id
     * @param KeyboardMarkup $reply_markup
     * @param bool $disable_notification
     *
     * @return mixed[]
     */
    public function sendSticker($chat_id, $sticker, $reply_to_message_id = null, $reply_markup = null, $disable_notification = false)
    {
        $data = compact('chat_id', 'sticker', 'reply_to_message_id', 'reply_markup', 'disable_notification');
        if (!file_exists($sticker) || filter_var($sticker, FILTER_VALIDATE_URL))
            return $this->sendRequest('sendSticker', $data);
        return $this->uploadFile('sendSticker', $data);
    }

    /**
     * Use this method to set a custom title for an administrator in a supergroup promoted by the bot.
     *
     * @link https://core.telegram.org/bots/api#setchatadministratorcustomtitle
     *
     * @param $chat_id
     * @param $user_id
     * @param $custom_title
     *
     * @return array|mixed
     */
    public function setChatAdministratorCustomTitle($chat_id, $user_id, $custom_title)
    {
        $params = compact('chat_id', 'user_id', 'custom_title');
        return $this->sendRequest('setChatAdministratorCustomTitle', $params);
    }

    /**
     * Use this method to set default chat permissions for all members. The bot must be an administrator in the group or
     * a supergroup for this to work and must have the can_restrict_members admin rights.
     *
     * @link https://core.telegram.org/bots/api#setchatpermissions
     *
     * @param $chat_id
     * @param $permissions
     *
     * @return array|mixed
     */
    public function setChatPermissions($chat_id, $permissions)
    {
        $params = compact('chat_id', 'permissions');
        return $this->sendRequest('setChatPermissions', $params);
    }

    /**
     * Use this method to generate a new invite link for a chat; any previously generated link is revoked. The bot must
     * be an administrator in the chat for this to work and must have the appropriate admin rights.
     *
     * @link https://core.telegram.org/bots/api#exportchatinvitelink
     *
     * @param int $chat_id
     *
     * @return array|mixed
     */
    public function exportChatInviteLink($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('exportChatInviteLink', $params);
    }

    public function stopPoll($chat_id, $message_id, $reply_markup = null){
        $params = compact('chat_id', 'message_id', 'reply_markup');
        $this->sendRequest("stopPoll", $params);
    }

    /**
     *  Send Game
     *
     * @link https://core.telegram.org/bots/api#sendgame
     *
     * @param int $chat_id
     * @param string $game_short_name
     * @param bool disable_notification
     * @param int reply_to_message_id
     * @param Keyboard reply_markup
     *
     */
    public function sendGame($chat_id, $game_short_name, $disable_notification = null, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'game_short_name', 'disable_notification', 'reply_to_message_id', 'reply_markup');
        return $this->sendRequest('sendGame', $params);
    }

    /**
     *   Send Invoice
     *
     * @link https://core.telegram.org/bots/api#sendinvoice
     * @param int $chat_id
     * @param string $title
     * @param string $description
     * @param string $payload
     * @param string $provider_token
     * @param string $start_parameter
     * @param string $currency
     * @param array $prices
     * @param string $photo_url
     * @param int $photo_size
     * @param int $photo_width
     * @param int $photo_height
     * @param bool $need_name
     * @param bool $need_email
     * @param bool $need_shipping_address
     * @param bool $is_flexible
     * @param bool $disable_notification
     * @param int $reply_to_message_id
     * @param int $reply_markup
     */
    public function sendInvoice($chat_id, $title, $description, $payload, $provider_token, $start_parameter, $currency, $prices, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = false, $need_email = false, $need_shipping_address = false, $is_flexible = false, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = array_filter(compact('chat_id', 'title', 'description', 'payload', 'provider_token', 'start_parameter', 'currency', 'prices', 'photo_url', 'photo_size', 'photo_width', 'photo_height', 'need_name', 'need_email', 'need_shipping_address', 'is_flexible', 'disable_notification', 'reply_to_message_id', 'reply_markup'));
        return $this->sendRequest('sendInvoice', $params);
    }

    /**
     *   Answer Shipping Query
     *
     * @link https://core.telegram.org/bots/api#answershippingquery
     * @param string $shipping_query_id
     * @param bool $ok
     * @param array $shipping_options
     * @param string $error_message
     */
    public function answerShippingQuery($shipping_query_id, $ok, $shipping_options = null, $error_message = null)
    {
        $params = array_filter(compact('shipping_query_id', 'ok', 'shipping_options', 'error_message'));
        return $this->sendRequest('answerShippingQuery', $params);
    }

    /**
     *   Answer Pre Checkout Query
     *
     * @link https://core.telegram.org/bots/api#answerprecheckoutquery
     * @param string $pre_checkout_query_id
     * @param bool $ok
     * @param string $error_message
     */
    public function answerPreCheckoutQuery($pre_checkout_query_id, $ok, $error_message = null)
    {
        $params = array_filter(compact('pre_checkout_query_id', 'ok', 'error_message'));
        return $this->sendRequest('answerPreCheckoutQuery', $params);
    }

    /**
     *   Delete Message
     *
     * @link https://core.telegram.org/bots/api#deletemessage
     * @param int $chat_id
     * @param int $message_id
     */
    public function deleteMessage($chat_id, $message_id)
    {
        $params = array_filter(compact('chat_id', 'message_id'));
        return $this->sendRequest('deleteMessage', $params);
    }

    /**
     *  Set Game Score
     *
     * @link https://core.telegram.org/bots/api#setgamescore
     *
     * @param int $user_id
     * @param int $score
     * @param int $chat_id
     * @param int $message_id
     * @param string $inline_message_id
     * @param bool $edit_message
     *
     */
    public function setGameScore($user_id, $game_short_name, $chat_id = null, $message_id = null, $inline_message_id = null, $disable_edit_message = true)
    {
        $params = array_filter(compact('user_id', 'game_short_name', 'chat_id', 'message_id', 'inline_message_id', 'disable_edit_message'));
        return $this->sendRequest('setGameScore', $params);
    }

    /**
     *  Set Game Score
     *
     * @link https://core.telegram.org/bots/api#getgamehighscores
     *
     * @param int $user_id
     * @param int $chat_id
     * @param int $message_id
     * @param string $inline_message_id
     *
     */
    public function getGameHighScores($user_id, $chat_id = null, $message_id = null, $inline_message_id = null)
    {
        $params = array_filter(compact('user_id', 'chat_id', 'message_id', 'inline_message_id'));
        return $this->getGameHighScores('getGameHighScores', $params);
    }

    /**
     * Set Chat Photo
     *
     * @param int $chat_id
     * @param InputFile $photo
     */
    public function setChatPhoto($chat_id, $photo)
    {
        $data = compact('chat_id', 'photo');
        if (!file_exists($photo) || filter_var($photo, FILTER_VALIDATE_URL))
            return $this->sendRequest('setChatPhoto', $data);
        return $this->uploadFile('setChatPhoto', $data);
    }

    /**
     * Delete Chat Photo
     *
     * @param int $chat_id
     */
    public function deleteChatPhoto($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('deleteChatPhoto', $params);
    }

    /**
     * Set Chat Title
     *
     * @param int $chat_id
     * @param string $title
     */
    public function setChatTitle($chat_id, $title)
    {
        $data = compact('chat_id', 'title');
        return $this->sendRequest('setChatTitle', $data);
    }

    /**
     * Set Chat Description
     *
     * @param int $chat_id
     * @param string $description
     */
    public function setChatDescription($chat_id, $description)
    {
        $data = compact('chat_id', 'description');
        return $this->sendRequest('setChatDescription', $data);
    }

    /**
     * Pin Chat Message
     *
     * @param int $chat_id
     * @param int $message_id
     * @param bool $disable_notification
     */
    public function pinChatMessage($chat_id, $message_id, $disable_notification = false)
    {
        $params = compact('chat_id', 'message_id', 'disable_notification');
        return $this->sendRequest('pinChatMessage', $params);
    }

    /**
     * Unpin Chat Message
     *
     * @param int $chat_id
     */
    public function unpinChatMessage($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('unpinChatMessage', $params);
    }

    /**
     * Send answers to callback sent from inline keyboards
     *
     * @param string $callback_query_id
     * @param string $text
     * @param bool $show_alert
     *
     * @return Array
     */
    public function answerCallbackQuery($callback_query_id, $text, $show_alert = false, $url = null)
    {
        $params = compact('callback_query_id', 'text', 'show_alert', 'url');
        return $this->sendRequest('answerCallbackQuery', $params);
    }

    /**
     * Edit text message sent by the bot
     *
     * @param int $chat_id
     * @param int $message_id
     * @param string $inline_message_id
     * @param string $text
     * @param string $parse_mode
     * @param bool $disable_web_page_preview
     * @param InlineKeyboardMarkup $reply_markup
     *
     * @return Array
     */
    public function editMessageText($chat_id = null, $message_id = null, $inline_message_id = null, $text, $parse_mode = null, $disable_web_page_preview = false, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'text', 'parse_mode', 'disable_web_page_preview', 'reply_markup');
        return $this->sendRequest('editMessageText', $params);
    }

    /**
     * Edit caption of a message sent by the bot
     *
     * @param int $chat_id
     * @param int $message_id
     * @param string $inline_message_id
     * @param string $caption
     * @param InlineKeyboardMarkup $reply_markup
     *
     * @return Array
     */
    public function editMessageCaption($chat_id = null, $message_id = null, $inline_message_id = null, $caption, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'caption', 'reply_markup');
        return $this->sendRequest('editMessageCaption', $params);
    }

    /**
     * Edit inline reply markup of a message sent by the bot
     *
     * @param int $chat_id
     * @param int $message_id
     * @param string $inline_message_id
     * @param InlineKeyboardMarkup $reply_markup
     *
     * @return Array
     */
    public function editMessageReplyMarkup($chat_id = null, $message_id = null, $inline_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'reply_markup');
        return $this->sendRequest('editMessageReplyMarkup', $params);
    }

    /**
     *Send inline query results
     *
     * @param string $inline_query_id
     * @param Array of InlineQueryResult $results
     * @param int $cache_time
     * @param bool $is_personal
     * @param string $next_offset
     * @param string $switch_pm_text
     * @param strin $switch_pm_parameter
     *
     * @return Array
     */
    public function answerInlineQuery($inline_query_id, $results, $cache_time = 300, $is_personal = false, $next_offset = null, $switch_pm_text = null, $switch_pm_parameter = null)
    {
        $params = compact('inline_query_id', 'results', 'cache_time', 'is_personal', 'next_offset', 'switch_pm_text', 'switch_pm_parameter');
        return $this->sendRequest('answerInlineQuery', $params);
    }

    /**
     *Use this method to get up to date information about the chat (current name of the user for one-on-one conversations, current username of a user, group or channel, etc.). Returns a Chat object on success.
     *
     * @param int $chat_id
     *
     * @return Array
     */
    public function getChat($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('getChat', $params);
    }

    /**
     * Use this method to get a list of administrators in a chat. On success, returns an Array of ChatMember objects that contains information about all chat administrators except other bots. If the chat is a group or a supergroup and no administrators were appointed, only the creator will be returned.
     *
     * @param int $chat_id
     *
     * @return Array
     */
    public function getChatAdministrators($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('getChatAdministrators', $params);
    }

    /**
     *Use this method to get the number of members in a chat. Returns Int on success.
     *
     * @param int $chat_id
     *
     * @return int
     */
    public function getChatMembersCount($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('getChatMembersCount', $params);
    }

    /**
     *Use this method to get information about a member of a chat. Returns a ChatMember object on success.
     *
     * @param int $chat_id
     * @param int $user_id
     *
     * @return int
     */
    public function getChatMember($chat_id, $user_id)
    {
        $params = compact('chat_id', 'user_id');
        return $this->sendRequest('getChatMember', $params);
    }

    /**
     * Set Chat Sticker Set
     *
     * @param int $chat_id
     * @param string $sticker_set_name
     *
     * @return int
     */
    public function setChatStickerSet($chat_id, $sticker_set_name)
    {
        $params = compact('chat_id', 'sticker_set_name');
        return $this->sendRequest('setChatStickerSet', $params);
    }

    /**
     * Delete Chat Sticker Set
     *
     * @param int $chat_id
     *
     * @return int
     */
    public function deleteChatStickerSet($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('deleteChatStickerSet', $params);
    }

    /**
     *Use this method for your bot to leave a group, supergroup or channel. Returns True on success.
     *
     * @param int $chat_id
     *
     * @return int
     */
    public function leaveChat($chat_id)
    {
        $params = compact('chat_id');
        return $this->sendRequest('leaveChat', $params);
    }

    /**
     * Returns webhook updates sent by Telegram.
     * Works only if you set a webhook.
     *
     * @return Array
     * @see setWebhook
     *
     */
    public function getWebhookUpdates()
    {
        $body = $_GET;
        return $body;
    }

    /**
     * Builds a custom keyboard markup.
     *
     * @link https://core.telegram.org/bots/api#replykeyboardmarkup
     *
     * @param array $keyboard
     * @param bool $resize_keyboard
     * @param bool $one_time_keyboard
     * @param bool $selective
     *
     * @return string
     */
    public function replyKeyboardMarkup($keyboard, $resize_keyboard = false, $one_time_keyboard = false, $selective = false)
    {
        return json_encode(compact('keyboard', 'resize_keyboard', 'one_time_keyboard', 'selective'));
    }

    /**
     *Build a Keyboard Button
     *
     * @param text
     * @param request_contact default false
     * @param request_location default false
     *
     * @return string
     */
    public function keyboardButton($text, $request_contact = false, $request_location = false)
    {
        if ($request_contact != false) {
            return json_encode(compact('text', 'request_contact'));
        } elseif ($request_location != false) {
            return json_encode(compact('text', 'request_location'));
        } else {
            return json_encode(compact('text'));
        }
    }

    /**
     *Build InlineKeyboardMarkup
     *
     * @param Array of Array of InlineKeyboardButton $inline_keyboard
     *
     * @return string
     */
    public function inlineKeyboardMarkup($inline_keyboard)
    {
        $inline_keyboard = json_encode($inline_keyboard);
        return json_encode(compact($inline_keyboard));
    }

    /**
     *Build a Keyboard Button
     *
     * @param text
     * @param url
     * @param callback_data default null
     * @param switch_inline_query default null
     * @param switch_inline_query_current_chat
     * @param callback game
     *
     * @return string
     */
    public function inlineKeyboardButton($text, $url = false, $callback_data = false, $switch_inline_query = false, $switch_inline_query_current_chat = false, $callback_game = false, $pay = false)
    {
        if ($url != false) {
            return json_encode(compact('text', 'url'));
        } elseif ($callback_data != false) {
            return json_encode(compact('text', 'callback_data'));
        } elseif ($switch_inline_query != false) {
            return json_encode(compact('text', 'switch_inline_query'));
        } elseif ($switch_inline_query_current_chat != false) {
            return json_encode(compact('text', 'switch_inline_query_current_chat'));
        } elseif ($callback_game != false) {
            return json_encode(compact('text', 'callback_game'));
        } elseif ($pay != false) {
            return json_encode(compact('text', 'pay'));
        } else {
            throw new TelegramException("Text keyboard are not allowed");
        }
    }

    /**
     *Build InlineQueryResultArticle
     *
     * @param string $type = 'article'
     * @param string $id
     * @param string $title
     * @param InputMessageContent $input_message_content
     * @param InlineKeyboardMarkup $reply_markup
     * @param string $url
     * @param bool $hide_url
     * @param string $description
     * @param string $thumb_url
     * @param int $thumb_width
     * @param int $thumb_height
     *
     * @return string
     */
    public function inlineQueryResultArticle($id, $title, $input_message_content, $reply_markup = null, $url = null, $hide_url = false, $description = null, $thumb_url = null, $thumb_width = null, $thumb_height = null)
    {
        $type = 'article';
        return json_encode(array_filter(compact('type', 'id', 'title', 'input_message_content', 'reply_markup', 'url', 'hide_url', 'description', 'thumb_url', 'thumb_width', 'thumb_height')));
    }

    /**
     * Hide the current custom keyboard and display the default letter-keyboard.
     *
     * @link https://core.telegram.org/bots/api#replykeyboardhide
     *
     * @param bool $selective
     *
     * @return string
     */
    public static function replyKeyboardHide($selective = false)
    {
        $remove_keyboard = true;
        return json_encode(compact('remove_keyboard', 'selective'));
    }

    /**
     * Display a reply interface to the user (act as if the user has selected the bots message and tapped 'Reply').
     *
     * @link https://core.telegram.org/bots/api#forcereply
     *
     * @param bool $selective
     *
     * @return string
     */
    public static function forceReply($selective = false)
    {
        $force_reply = true;
        return json_encode(compact('force_reply', 'selective'));
    }

    private function url_get_contents($Url)
    {
        if (!function_exists('curl_init')) {
            die('CURL is not installed!');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function sendRequest($method, $params)
    {
        $this->lastUrl = $this->baseURL . $method . '?' . http_build_query($params);
        $this->lastResponse = json_decode($this->url_get_contents($this->baseURL . $method . '?' . http_build_query($params)), true);
        if (!empty($this->triggerHttp) && function_exists($this->triggerHttp)) {
            call_user_func($this->triggerHttp, $this->lastResponse);
        }
        if(!$this->lastResponse["ok"]) {
            if(array_key_exists("parameters", $this->lastResponse) && array_key_exists("retry_after", $this->lastResponse["parameters"])) {
                sleep($this->lastResponse["parameters"]["retry_after"]);
                return $this->sendRequest($method, $params);
            }
        }
        return $this->lastResponse;
    }

    public function getDownloadFileLink() {
        return substr(self::BASE_URL, 0, strlen(self::BASE_URL) - 3) . "file/bot" . $this->token . "/" . $this->lastResponse["result"]["file_path"];
    }

    /*
    private function uploadFile($method, $data)
    {
    $key = array(
    'sendPhoto'    => 'photo',
    'sendAudio'    => 'audio',
    'sendDocument' => 'document',
    'sendSticker'  => 'sticker',
    'sendVideo'    => 'video',
    'setWebhook'   => 'certificate'
    );
    if (filter_var($data[$key[$method]], FILTER_VALIDATE_URL))
    {
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . mt_rand(0, 9999);
    $url = true;
    file_put_contents($file, file_get_contents($data[$key[$method]]));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file);
    $extensions = array(
    'image/jpeg'  => '.jpg',
    'image/png'   =>  '.png',
    'image/gif'   =>  '.gif',
    'image/bmp'   =>  '.bmp',
    'image/tiff'  =>  '.tif',
    'audio/ogg'   =>  '.ogg',
    'video/mp4'   =>  '.mp4',
    'image/webp'  =>  '.webp'
    );
    if ($method != 'sendDocument')
    {
    if (!array_key_exists($mime_type, $extensions))
    {
    unlink($file);
    throw new TelegramException('Bad file type/extension');
    }
    }
    $newFile = $file . $extensions[$mime_type];
    rename($file, $newFile);
    $data[$key[$method]] = new CurlFile($newFile, $mime_type, $newFile);
    }
    else
    {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $data[$key[$method]]);
    $data[$key[$method]] = new CurlFile($data[$key[$method]], $mime_type, $data[$key[$method]]);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->baseURL . $method);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = json_decode(curl_exec($ch), true);
    if ($url)
    unlink($newFile);
    return $response;
    }
    */
    private function uploadFile($method, $data)
    {

        $key = array(
            'sendPhoto' => 'photo',
            'sendAudio' => 'audio',
            'sendVoice' => 'voice',
            'sendDocument' => 'document',
            'sendSticker' => 'sticker',
            'sendVideo' => 'video',
            'setWebhook' => 'certificate'
        );

        $filename = realpath($data[$key[$method]]);

        $data[$key[$method]] = null;

        $data = array_filter($data);

        $destination = $this->baseURL . $method . '?' . http_build_query($data);

        $eol = "\r\n";
        $data = '';

        $mime_boundary = md5(time());

        $data .= '--' . $mime_boundary . $eol;
        /*$data .= 'Content-Disposition: form-data; name="chat_id"' . $eol . $eol;
        $data .= "-20707046" . $eol;
        $data .= '--' . $mime_boundary . $eol;*/
        $data .= 'Content-Disposition: form-data; name="' . $key[$method] . '"; filename="' . basename($filename) . '"' . $eol;
        $data .= 'Content-Type: text/plain' . $eol;
        $data .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
        $data .= file_get_contents($filename) . $eol;
        $data .= "--" . $mime_boundary . "--" . $eol . $eol; // finish with two eol's!!

        $params = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary=' . $mime_boundary . $eol,
                'content' => $data
            )
        );

        $ctx = stream_context_create($params);
        return json_decode(@file_get_contents($destination, FILE_TEXT, $ctx), true);

    }
}