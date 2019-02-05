<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/13/2018
 * Time: 5:44 PM
 */

/**
 * Class Router
 *
 * All requests must go through the POST method in JSON format
 *
 * This is my simplified version of JSON-RPC protocol made only for this framework.
 * All requests must be in the following form:
 *  [
 *      {
 *          "method": "<controler>.<method>",
 *          "params": ["<array of params needed for 'method'"]
 *      },
 *      {
 *          "method": "<controler>.<method>",
 *          "params": ["<array of params needed for 'method'"]
 *      } ...
 *  ]
 *  on this way, in one http request, we can to get more results
 *
 * Result is in JSON form too:
 *  {
 *      "success": "<true/false>",
 *      "errors": ["array of errors if exists"],
 *      "results":
 *      [
 *          {
 *              "method": "<controler>.<method>",
 *              "data": "<result of method executing>"
 *          },
 *          {
 *              "method": "<controler>.<method>",
 *              "data": "<result of method executing>"
 *          } ...
 *      ]
 *   }
 */
class Router
{
    /**
     * @var stdClass
     * response object
     */
    private $response;

    /**
     * @var array|mixed
     * raw requests
     */
    private $rawRequests;

    /**
     * @var array
     * array of prepared requests
     */
    private $preparedRequests = [];

    /**
     * @var array
     * set of controllers to be called
     */
    private $controllers = [];

    /**
     * Router constructor.
     */
    public function __construct()
    {
        // init response
        $this->response = new stdClass();
        $this->response->success = false;
        $this->response->errors = [];
        $this->response->results = [];

        // get requests JSON
        $data = json_decode(file_get_contents('php://input'), true);
        if(is_array($data)) {
            $this->rawRequests = $data;
            $this->handleRequests();
        } else {
            $this->response->errors[] = 'Wrong request data';
        }

        header('Content-Type: application/json');
        print(json_encode($this->response, JSON_NUMERIC_CHECK ));
    }

    /**
     * handles all requests
     */
    private function handleRequests()
    {
        if($this->prepareRequests()) {
            foreach($this->preparedRequests as $preparedRequest) {
                if(!$this->executeRequest($preparedRequest)) {
                    return;
                }
            }
            // if all the requests are executed set success as true
            $this->response->success = true;
        } else {
            $this->response->success = false;
        }
    }

    /**
     * @return bool
     * prepares all requests
     */
    private function prepareRequests()
    {
        foreach ($this->rawRequests as $request) {
            if(!$this->prepareRequest($request)){
                return false;
            }
        }
        return true;
    }

    /**
     * @param $request
     * @return bool
     *
     * prepares one request and instantiates the controller class
     */
    private function prepareRequest($request)
    {
        // holds requests data
        $preparedRequest = new stdClass();

        // check params
        if(!is_array($request['params'])){
            $this->response->errors[] = 'Wrong parameters';
            return false;
        }
        $preparedRequest->params = $request['params'];

        // check controller and method
        if(empty($request['method']) && is_string($request['method'])) {
            $this->response->errors[] = 'Wrong request';
            return false;
        }

        list($controller, $method) = explode(".", $request['method'], 2);
        $controller = ucfirst($controller)."Controller";

        // check if controller exists
        if(!file_exists(APP.'controllers'.DIRECTORY_SEPARATOR.$controller.'.php')) {
            $this->response->errors[] = "Controller $controller does not exist";
            return false;
        }

        if (!array_key_exists($controller, $this->controllers)) {
            $this->controllers[$controller] = new $controller();
        }
        $preparedRequest->controller = $controller;

        // check if method exists
        if(!method_exists($this->controllers[$controller], $method)) {
            $this->response->errors[] = "Method $method  does not exist";
            return false;
        }

        $preparedRequest->method = $method;
        $this->preparedRequests[] = $preparedRequest;
        return true;
    }

    /**
     * @param $preparedRequest
     * @return bool
     *
     * Executes given request and result puts in response object
     */
    private function executeRequest($preparedRequest)
    {
        $controllerClassInstance = $this->controllers[$preparedRequest->controller];

        $result = call_user_func_array(
            array($controllerClassInstance, $preparedRequest->method),
            $preparedRequest->params
        );
        if($result === false) {
            return false;
        }
        $this->response->results[] = $result;

        return true;
    }
}
