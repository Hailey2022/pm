<?php

namespace api\demo\swagger\response;

use api\swagger\reponse\SuccessResponse;

use OpenApi\Annotations as OA;



class DemoArticlesIndexResponse extends SuccessResponse
{

    
    public $data;

}


class DemoArticlesIndexResponseData
{

    
    public $total;

    
    public $list;

}



class DemoArticlesIndexResponseDataListItem
{
    
    public $name;


}
