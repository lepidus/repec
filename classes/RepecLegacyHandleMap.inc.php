<?php

/**
 * @file plugins/generic/repec/classes/RepecLegacyHandleMap.inc.php
 *
 * @class RepecLegacyHandleMap
 * @ingroup plugins_generic_repec
 *
 * @brief Parses and validates legacy RePEc article handle maps.
 */

class RepecLegacyHandleMap
{
    public function parseJson($contents)
    {
        $data = json_decode((string) $contents, true);
        if (!is_array($data) || $this->isList($data)) {
            return array(null, 'plugins.generic.repec.settings.legacyHandlesJsonInvalid');
        }

        $handles = array();
        foreach ($data as $submissionId => $handle) {
            $submissionId = trim((string) $submissionId);
            $handle = trim((string) $handle);

            if (!preg_match('/^[1-9][0-9]*$/', $submissionId)) {
                return array(null, 'plugins.generic.repec.settings.legacyHandlesIdInvalid');
            }
            if (!$this->isValidHandle($handle)) {
                return array(null, 'plugins.generic.repec.settings.legacyHandlesHandleInvalid');
            }

            $handles[$submissionId] = $handle;
        }

        if (empty($handles)) {
            return array(null, 'plugins.generic.repec.settings.legacyHandlesEmpty');
        }

        ksort($handles, SORT_NUMERIC);
        return array($handles, null);
    }

    public function decode($contents)
    {
        $contents = $this->decodeStoredValue($contents);
        $data = json_decode((string) $contents, true);
        return is_array($data) && !$this->isList($data) ? $data : array();
    }

    public function encode($handles)
    {
        return json_encode((object) $handles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    public function encodeForStorage($handles)
    {
        $json = $this->encode($handles);
        if (function_exists('gzcompress')) {
            return 'gz64:' . base64_encode(gzcompress($json));
        }
        return $json;
    }

    public function decodeStoredValue($contents)
    {
        $contents = (string) $contents;
        if (substr($contents, 0, 5) !== 'gz64:') {
            return $contents;
        }
        if (!function_exists('gzuncompress')) {
            return '';
        }

        $decoded = base64_decode(substr($contents, 5), true);
        if ($decoded === false) {
            return '';
        }

        $uncompressed = @gzuncompress($decoded);
        return $uncompressed === false ? '' : $uncompressed;
    }

    private function isValidHandle($handle)
    {
        return preg_match('/^RePEc:[A-Za-z0-9][A-Za-z0-9:._;(),?\/-]*$/', $handle);
    }

    private function isList($data)
    {
        return array_keys($data) === range(0, count($data) - 1);
    }
}
