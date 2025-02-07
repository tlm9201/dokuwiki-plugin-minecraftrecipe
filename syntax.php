<?php
use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_minecraftrecipe extends SyntaxPlugin {

    public function getType() {
        return 'container';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 155;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\<recipe(?:\s+type="[^"]*")?\>.*?\</recipe\>', $mode, 'plugin_minecraftrecipe');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        $type = 'crafting'; // default type
        if(preg_match('/type="([^"]*)"/', $match, $matches)) {
            $type = $matches[1];
        }

        $content = preg_replace('/^\<recipe(?:\s+type="[^"]*")?\>|\<\/recipe\>$/i', '', $match);
        $lines = explode("\n", trim($content));

        switch($type) {
            case 'smelting':
                return $this->_handleSmelting($lines);
            case 'brewing':
                return $this->_handleBrewing($lines);
            case 'crafting':
            default:
                return $this->_handleCrafting($lines);
        }
    }

    protected function _handleCrafting($lines) {
        $recipe = array();
        $result = array();
    
        foreach($lines as $line) {
            $line = trim($line);
            if(empty($line)) continue;
    
            if(strpos($line, '->') !== false) {
                $resultItems = explode(',', trim(substr($line, 2)));
                $result = array_map('trim', $resultItems);
            } else {
                $slots = array_filter(explode(' ', $line));
                $row = array();
                foreach($slots as $slot) {
                    $items = array_map('trim', explode(',', $slot));
                    $row[] = $items;
                }
                while(count($row) < 3) {
                    $row[] = array('empty');
                }
                if(!empty($row)) {
                    $recipe[] = $row;
                }
            }
        }
    
        // pad to 3 rows with empty slots
        while(count($recipe) < 3) {
            $recipe[] = array(array('empty'), array('empty'), array('empty'));
        }
    
        return array(
            'type' => 'crafting',
            'recipe' => $recipe,
            'result' => $result,
            'shapeless' => false
        );
    }
    
    protected function _handleSmelting($lines) {
        $input = array();
        $fuel = array();
        $result = array();
    
        foreach($lines as $index => $line) {
            $line = trim($line);
            if(empty($line)) continue;
    
            if(strpos($line, '->') !== false) {
                $resultItems = explode(',', trim(substr($line, 2)));
                $result = array_map('trim', $resultItems);
            } else if($index === 1) {  // Second line is fuel
                $fuelItems = explode(',', $line);
                $fuel = array_map('trim', $fuelItems);
            } else if($index === 0) {  // First line is input
                $inputItems = explode(',', $line);
                $input = array_map('trim', $inputItems);
            }
        }
    
        if(empty($fuel)) {
            $fuel = array('empty');
        }
    
        return array(
            'type' => 'smelting',
            'input' => $input,
            'fuel' => $fuel,
            'result' => $result
        );
    }

    protected function _handleBrewing($lines) {
        $reagent = array();
        $potions = array();
    
        foreach($lines as $line) {
            $line = trim($line);
            if(empty($line)) continue;
    
            if(strpos($line, '->') !== false) {
                $potionItems = explode(' ', trim(substr($line, 2)));
                foreach($potionItems as $potion) {
                    $potions[] = array_map('trim', explode(',', $potion));
                }
            } else {
                // first line is reagent
                $reagentItems = explode(',', $line);
                $reagent = array_map('trim', $reagentItems);
            }
        }
    
        while(count($potions) < 3) {
            $potions[] = array('empty');
        }
    
        return array(
            'type' => 'brewing',
            'reagent' => $reagent,
            'potions' => $potions
        );
    }
    
    protected function _renderBrewing($renderer, $data) {
        $renderer->doc .= '<div class="minecraft-recipe brewing">';
        $renderer->doc .= '<div class="brewing-layout">';
    
        $renderer->doc .= '<div class="bubbles"></div>';
        $renderer->doc .= '<div class="vertical-arrow"></div>';
        $renderer->doc .= '<div class="pipes"></div>';
    
        // reagent slot
        $renderer->doc .= '<div class="reagent grid">';
        $renderer->doc .= $this->_renderItem($data['reagent']);
        $renderer->doc .= '</div>';
    
        // potion slots
        foreach($data['potions'] as $index => $potion) {
            $renderer->doc .= '<div class="grid potion-' . ($index + 1) . '">';
            $renderer->doc .= $this->_renderItem($potion);
            $renderer->doc .= '</div>';
        }
    
        $renderer->doc .= '</div></div>';
        return true;
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if($mode != 'xhtml') return false;
        if(!is_array($data)) return false;

        switch($data['type']) {
            case 'smelting':
                return $this->_renderSmelting($renderer, $data);
            case 'brewing':
                return $this->_renderBrewing($renderer, $data);
            case 'crafting':
            default:
                return $this->_renderCrafting($renderer, $data);
        }
    }

    protected function _renderCrafting($renderer, $data) {
        $renderer->doc .= '<div class="minecraft-recipe">';
        $renderer->doc .= '<div class="crafting">';
        $renderer->doc .= '<div class="recipe">';
        $renderer->doc .= '<div class="table-grid">';
    
        if(isset($data['recipe'])) {
            foreach($data['recipe'] as $row) {
                foreach($row as $item) {
                    $renderer->doc .= '<span class="grid">';
                    $renderer->doc .= $this->_renderItem($item);
                    $renderer->doc .= '</span>';
                }
            }
        }
    
        $renderer->doc .= '</div></div>';
        $renderer->doc .= '<div class="arrow"></div>';
        $renderer->doc .= '<div class="crafting-table-output">';
        $renderer->doc .= '<span class="grid-large">';
        if(isset($data['result'])) {
            $renderer->doc .= $this->_renderItem($data['result']);
        }
        $renderer->doc .= '</span></div></div></div>';
    
        return true;
    }

    protected function _renderSmelting($renderer, $data) {
        $renderer->doc .= '<div class="minecraft-recipe smelting">';
        $renderer->doc .= '<div class="smelting-layout">';

        // input
        $renderer->doc .= '<div class="input"><span class="grid">';
        $renderer->doc .= $this->_renderItem($data['input']);
        $renderer->doc .= '</span></div>';

        // fire icon
        $renderer->doc .= '<div class="fire"></div>';

        // fuel
        $renderer->doc .= '<div class="fuel"><span class="grid">';
        $renderer->doc .= $this->_renderItem($data['fuel']);
        $renderer->doc .= '</span></div>';

        // arrow
        $renderer->doc .= '<div class="arrow"></div>';

        // result
        $renderer->doc .= '<div class="output"><span class="grid-large">';
        $renderer->doc .= $this->_renderItem($data['result']);
        $renderer->doc .= '</span></div>';

        $renderer->doc .= '</div></div>';
        return true;
    }

    protected function _renderItem($items) {
        if(!is_array($items)) {
            $items = array($items);
        }
    
        $allEmpty = true;
        foreach($items as $item) {
            $item = strtolower(trim($item));
            if($item !== 'empty' && $item !== 'x') {
                $allEmpty = false;
                break;
            }
        }
    
        if($allEmpty) return '';
    
        $output = '<div class="item-cycle">';
        foreach($items as $index => $item) {
            $item = trim($item);
            if(strtolower($item) === 'x') continue;
            if(strtolower($item) === 'empty') continue;
    
            $localPath = DOKU_PLUGIN . 'minecraftrecipe/images/item/' . strtolower($item) . '.png';
    
            if(file_exists($localPath)) {
                $src = DOKU_BASE . 'lib/plugins/minecraftrecipe/images/item/' . strtolower($item) . '.png';
            } else {
                $wikiItem = str_replace(' ', '_', $item);
                $src = 'https://minecraft.wiki/images/Invicon_' . $wikiItem . '.png'; // fallback to minecraft.wiki
            }
    
            $visibility = ($index === 0) ? '' : ' style="display: none;"';
            $output .= '<img src="'.hsc($src).'" alt="'.hsc($item).'" title="'.hsc($item).'" class="item-icon cycle-item"'.$visibility.' data-cycle-index="'.$index.'" />';
        }
        $output .= '</div>';
    
        return $output;
    }
}