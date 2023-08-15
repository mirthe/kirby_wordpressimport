<?php
class sanitizeFileName
{
    function letsgo(string $fileName): string
    {
        // Remove multiple spaces
        $fileName = preg_replace('/\s+/', ' ', $fileName);

        // Replace spaces with hyphens
        $fileName = preg_replace('/\s/', '-', $fileName);

        // Replace german characters
        $dutchReplaceMap = [
            'ä' => 'a',
            'Ä' => 'A',
            'á' => 'a',
            'à' => 'a',
            'ü' => 'u',
            'Ü' => 'U',
            'ö' => 'o',
            'Ö' => 'O',
            'ó' => 'o',
            'ò' => 'o',
            'ß' => 'ss',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e'
        ];
        $fileName = str_replace(array_keys($dutchReplaceMap), $dutchReplaceMap, $fileName);

        // Remove everything but "normal" characters
        $fileName = preg_replace("([^\w\s\d\-])", '', $fileName);

        // Remove multiple hyphens because of contract and project name connection
        $fileName = preg_replace('/-+/', '-', $fileName);

        return $fileName;
    } 
}
?>