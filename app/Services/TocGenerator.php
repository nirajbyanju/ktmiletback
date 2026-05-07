<?php
// app/Services/TocGenerator.php

namespace App\Services;

class TocGenerator
{
    public function generateFromContent(string $content): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        
        $headings = $dom->getElementsByTagName('*');
        $toc = [];
        $idCounter = [];
        
        foreach ($headings as $heading) {
            if (in_array($heading->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
                $level = (int) substr($heading->tagName, 1);
                $text = $heading->textContent;
                
                // Generate ID from heading text
                $id = $this->generateSlug($text);
                
                // Handle duplicate IDs
                if (isset($idCounter[$id])) {
                    $idCounter[$id]++;
                    $id = $id . '-' . $idCounter[$id];
                } else {
                    $idCounter[$id] = 0;
                }
                
                // Add ID to heading element
                $heading->setAttribute('id', $id);
                
                $toc[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                    'children' => []
                ];
            }
        }
        
        // Nest headings based on levels
        $nestedToc = $this->nestHeadings($toc);
        
        return [
            'toc' => $nestedToc,
            'content' => $dom->saveHTML()
        ];
    }
    
    private function generateSlug(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }
    
    private function nestHeadings(array $headings): array
    {
        $result = [];
        $stack = [&$result];
        
        foreach ($headings as $heading) {
            $currentLevel = $heading['level'];
            
            // Find appropriate parent
            while (count($stack) > $currentLevel) {
                array_pop($stack);
            }
            
            $parent = &$stack[count($stack) - 1];
            
            if ($currentLevel == 1) {
                $parent[] = $heading;
            } else {
                if (!isset($parent[count($parent) - 1])) {
                    $parent[] = $heading;
                } else {
                    $parent[count($parent) - 1]['children'][] = $heading;
                }
            }
            
            $stack[] = &$parent[count($parent) - 1]['children'];
        }
        
        return $result;
    }
}