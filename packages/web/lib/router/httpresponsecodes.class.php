<?php
/**
 * Builds the response codes.
 *
 * PHP Version 5
 *
 * @category HTTPResponseCodes
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.com/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org/
 */
/**
 * Builds the response codes.
 *
 * @category HTTPResponseCodes
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.com/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org/
 */
class HTTPResponseCodes
{
    // Informational Codes
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;
    // Success
    public const HTTP_SUCCESS = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_IM_USED = 226;
    // Redirection
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_SWITCH_PROXY = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENT_REDIRECT = 308;
    // Client Errors
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIME_OUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_PAYLOAD_TOO_LARGE = 413;
    public const HTTP_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_TEAPOT = 418;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    // Server Errors
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
    // Unofficial
    public const HTTP_CHECKPOINT = 103;
    public const HTTP_EARLY_HINTS = 103;
    public const HTTP_METHOD_FAILURE = 420;
    public const HTTP_ENHANCE_YOUR_CALM = 420;
    public const HTTP_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;
    public const HTTP_INVALID_TOKEN = 498;
    public const HTTP_TOKEN_REQUIRED = 499;
    public const HTTP_BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const HTTP_SITE_IS_FROZEN = 530;
    public const HTTP_NETWORK_READ_TIMEOUT_ERROR = 598;
    public const HTTP_NETWORK_CONNECT_TIMEOUT_ERROR = 599;
    // IIS
    public const HTTP_LOGIN_TIME_OUT = 440;
    public const HTTP_RETRY_WITH = 449;
    public const HTTP_REDIRECT = 451;
    // nginx
    public const HTTP_NO_RESPONSE = 444;
    public const HTTP_SSL_CERTIFICATE_ERROR = 495;
    public const HTTP_SSL_CERTIFICATE_REQUIRED = 496;
    public const HTTP_REQUEST_SEND_TO_HTTPS_PORT = 497;
    public const HTTP_CLIENT_CLOSED_REQUEST = 499;
    // Cloudflare
    public const HTTP_UNKNOWN_ERROR = 520;
    public const HTTP_WEB_SERVER_IS_DOWN = 521;
    public const HTTP_CONNECTION_TIMED_OUT = 522;
    public const HTTP_ORIGIN_IS_UNREACHABLE = 523;
    public const HTTP_A_TIMEOUT_OCCURRED = 524;
    public const HTTP_SSL_HANDSHAKE_FAILED = 525;
    public const HTTP_INVALID_SSL_CERTIFICATE = 526;
    public const HTTP_RAILGUN_ERROR = 527;

    /**
     * Error codes begin where?
     *
     * @var int
     */
    private static $_errorCodesBeginAt = 400;
    /**
     * Messages to codes.
     *
     * @var array
     */
    private static $_messages = array(
        // Informational
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        102 => '102 Processing',
        // Success
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        207 => '207 Multi-Status',
        208 => '208 Already Reported',
        226 => '226 IM Used',
        // Redirect
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 Switch Proxy',
        307 => '307 Temporary Redirect',
        308 => '308 Permanent Redirect',
        // Client Errors
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Time-out',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Payload Too Large',
        414 => '414 URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => "418 I'm a teapot",
        421 => '421 Misdirect Request',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        424 => '424 Failed Dependency',
        426 => '426 Upgrade Required',
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        431 => '431 Request Header Fields Too Large',
        451 => '451 Unavailable For Legal Reasons',
        // Server Errors
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Time-out',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        507 => '507 Insufficient Storage',
        508 => '508 Loop Detected',
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required'
    );
    /**
     * Returns the server protocol.
     *
     * @return string
     */
    public static function getServerProtocol()
    {
        return filter_input(INPUT_SERVER, 'SERVER_PROTOCOL');
    }
    /**
     * Returns header string as appropriate.
     *
     * @param int $code The code to lookup
     *
     * @return string
     */
    public static function getMessageForCode($code)
    {
        return self::$_messages[$code];
    }
    /**
     * Returns if is error.
     *
     * @param int $code The code to test.
     *
     * @return bool
     */
    public static function isError($code)
    {
        return (is_numeric($code) && $code >= self::$_errorCodesBeginAt);
    }
    /**
     * Run the header based on code and exit.
     *
     * @param int    $code The code to pass.
     * @param string $msg  The message to send.
     *
     * @return void
     */
    public static function breakHead($code, $msg = '')
    {
        header(
            sprintf(
                '%s %s',
                self::getServerProtocol(),
                self::getMessageForCode($code)
            ),
            true,
            $code
        );
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        if (in_array($method, array('HEAD', 'OPTIONS'))) {
            header('Content-Length: 0');
        }
        header('Content-Type: application/json');
        echo $msg;
        exit;
    }
}
