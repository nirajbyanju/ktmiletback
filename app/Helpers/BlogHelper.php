<?php
// app/Helpers/BlogHelper.php
use Illuminate\Support\Str;

function addHeadingIds($html)
{
    $usedIds = [];

    return preg_replace_callback(
        '/<h([2-4])>(.*?)<\/h\1>/i',
        function ($matches) use (&$usedIds) {

            $baseId = Str::slug(strip_tags($matches[2]));
            $id = $baseId;
            $i = 1;

            while (in_array($id, $usedIds)) {
                $id = $baseId . '-' . $i++;
            }

            $usedIds[] = $id;

            return "<h{$matches[1]} id=\"{$id}\">{$matches[2]}</h{$matches[1]}>";
        },
        $html
    );
}
