<?php
namespace api\demo\swagger\request;
use OpenApi\Annotations as OA;
class DemoArticlesSave
{
    public $id;
    public $username;
    public $firstName;
    public $lastName;
    public $email;
    public $password;
    public $phone;
    public $userStatus;
}
