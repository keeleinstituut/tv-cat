<?php

class TMKeysUtils
{
    /**
     * @param string $inputTmKeys
     * @return array
     * @throws Exception
     */
    public static function parse(string $inputTmKeys): array
    {
        try {
            $tmKeys = array_values(
                array_filter(
                    array_map(
                        function ($inputTmKey) {
                            return self::parseTmKeysInput($inputTmKey);
                        },
                        explode(",", $inputTmKeys)
                    )
                )
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), -6);
        }


        foreach ($tmKeys as $__key_idx => $tm_key) {
            //from api a key is sent and the value is 'new'
            if (!empty($tm_key)) {

                $this_tm_key = [
                    'key' => $tm_key['key'],
                    'name' => null,
                    'r' => $tm_key['r'],
                    'w' => $tm_key['w'],
                    'tm' => true,
                    'is_private' => true,
                    'is_shared' => false
                ];

                $tmKeys[$__key_idx] = $this_tm_key;
            }

            $tmKeys[$__key_idx] = self::sanitizeTmKeyArr($tmKeys[$__key_idx]);
        }

        return $tmKeys;
    }

    private static function parseTmKeysInput($tmKeyString): ?array
    {
        $tmKeyString = trim($tmKeyString);
        $tmKeyInfo = explode(":", $tmKeyString);
        $read = true;
        $write = true;

        $permissionString = @$tmKeyInfo[1];
        //if the key is not set, return null. It will be filtered in the next lines.
        if (empty($tmKeyInfo[0])) {
            return null;
        } //if permissions are set, check if they are allowed or not and eventually set permissions

        //permission string check
        switch ($permissionString) {
            case 'r':
                $write = false;
                break;
            case 'w':
                $read = false;
                break;
            case 'rw':
            case ''  :
            case null:
                break;
            //permission string not allowed
            default:
                $allowed_permissions = implode(", ", Constants_TmKeyPermissions::$_accepted_grants);
                throw new Exception("Permission modifier string not allowed. Allowed: <empty>, $allowed_permissions");
        }

        return [
            'key' => $tmKeyInfo[0],
            'r' => $read,
            'w' => $write,
        ];
    }

    private static function sanitizeTmKeyArr($elem): array
    {
        $element = new TmKeyManagement_TmKeyStruct($elem);
        $element->complete_format = true;
        return TmKeyManagement_TmKeyManagement::sanitize($element)->toArray();

    }
}