<?php

declare(strict_types=1);

namespace Sipay;

use Sipay\Contracts\JsonConvertible;

class JsonBuilder
{
    private $json;

    public function __construct($json)
    {
        $this->json = $json;
    }

    public static function create()
    {
        return new JsonBuilder([]);
    }

    public static function fromJsonObject($json)
    {
        return new JsonBuilder($json);
    }

    /**
     * @return JsonBuilder
     */
    public function add($key, $value = null)
    {
        if (isset($value)) {
            if ($value instanceof JsonConvertible) {
                $this->json[$key] = $value->getJsonObject();
            } else {
                $this->json[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * @return JsonBuilder
     */
    public function addPrice($key, $value = null)
    {
        if (isset($value)) {
            $this->json[$key] = RequestFormatter::formatPrice($value);
        }

        return $this;
    }

    /**
     * @return JsonBuilder
     */
    public function addArray($key, array $array = null)
    {
        if (isset($array)) {
            foreach ($array as $index => $value) {
                if ($value instanceof JsonConvertible) {
                    $this->json[$key][$index] = $value->getJsonObject();
                } else {
                    $this->json[$key][$index] = $value;
                }
            }
        }

        return $this;
    }

    public function getObject()
    {
        return $this->json;
    }

    public static function jsonEncode($jsonObject)
    {
        return json_encode($jsonObject);
    }

    public static function jsonDecode($rawResult)
    {
        return json_decode($rawResult);
    }
}
