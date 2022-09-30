<?php
namespace Raryk\Highlight;

class Highlight {
    private $search = null;

    public function setSearch($search) {
        $this->search = $search;
    }
    
    public function string($text) {
        preg_match_all('/\bhttps?:\/\/[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $text, $match);
        
        $position = 0;
        $start = 0;
        $last = 0;
        
        $links = array();
        $array = array();
        
        foreach($match[0] as $item) {
            if(mb_stripos($text, $item, $position) !== false) {
                $links[] = array(
                    'min' => mb_stripos($text, $item, $position),
                    'max' => mb_stripos($text, $item, $position) + mb_strlen($item),
                    'text' => mb_substr($text, mb_stripos($text, $item, $position), mb_strlen($item))
                );
                
                $position = mb_stripos($text, $item, $position) + mb_strlen($item);
            }
        }
        
        if(!is_null($this->search) && mb_stripos($text, $this->search, $start) !== false) {
            while(($pos = mb_stripos($text, $this->search, $start)) !== false) {
                if($start == 0 && $pos != 0) {
                    $sort = array('min' => $start, 'max' => $pos);
                    
                    $filter = array_values(array_filter($links, function($link) use($sort) {
                        return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                    }));
                    
                    if(!empty($filter)) {
                        if($start < $filter[0]['min']) {
                            $array[$start] = array('type' => 'search', 'text' => mb_substr($text, $start, $filter[0]['min'] - $start));
                        }
                        
                        for($i = 0; $i < count($filter); $i++) {
                            if($filter[$i]['max'] > $pos) {
                                $array[$filter[$i]['min']] = array(
                                    'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                    'string' => array(
                                        array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $pos - $filter[$i]['min']))
                                    )
                                );
                                $last = $filter[$i]['min'];
                            } else {
                                $array[$filter[$i]['min']] = array(
                                    'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                    'string' => array(
                                        array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                    )
                                );
                                
                                if($i + 1 < count($filter)) {
                                    $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                } elseif($pos > $filter[$i]['max']) {
                                    $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $pos - $filter[$i]['max']));
                                }
                            }
                        }
                    } else {
                        $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start, $pos));
                    }
                }
                
                $start = $pos + mb_strlen($this->search);
                
                if(isset($array[$last]) && $pos > $last && $pos < $last + mb_strlen($array[$last]['link'])) {
                    if($start > $last + mb_strlen($array[$last]['link'])) {
                        $array[$last]['string'][] = array('type' => 'search', 'text' => mb_substr($text, $pos, $last + mb_strlen($array[$last]['link']) - $pos));
                        
                        $sort = array('min' => $last + mb_strlen($array[$last]['link']), 'max' => $start);
                        
                        $filter = array_values(array_filter($links, function($link) use($sort) {
                            return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                        }));
                        
                        if(!empty($filter)) {
                            if($last + mb_strlen($array[$last]['link']) < $filter[0]['min']) {
                                $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'search', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), $filter[0]['min'] - $last - mb_strlen($array[$last]['link'])));
                            }
                            
                            for($i = 0; $i < count($filter); $i++) {
                                if($filter[$i]['max'] > $start) {
                                    $array[$filter[$i]['min']] = array(
                                        'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                        'string' => array(
                                            array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['min'], $start - $filter[$i]['min']))
                                        )
                                    );
                                    $last = $filter[$i]['min'];
                                } else {
                                    $array[$filter[$i]['min']] = array(
                                        'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                        'string' => array(
                                            array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                        )
                                    );
                                    
                                    if($i + 1 < count($filter)) {
                                        $array[$filter[$i]['max']] = array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max'])
                                        );
                                    } elseif($start > $filter[$i]['max']) {
                                        $array[$filter[$i]['max']] = array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['max'], $start - $filter[$i]['max']));
                                    }
                                }
                            }
                        } else {
                            $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'search', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), $start - $last - mb_strlen($array[$last]['link'])));
                        }
                    } else {
                        $array[$last]['string'][] = array('type' => 'search', 'text' => mb_substr($text, $pos, $start - $pos));
                    }
                } else {
                    $sort = array('min' => $pos, 'max' => $start);
                    
                    $filter = array_values(array_filter($links, function($link) use($sort) {
                        return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                    }));
                    
                    if(!empty($filter)) {
                        if($pos < $filter[0]['min']) {
                            $array[$pos] = array('type' => 'search', 'text' => mb_substr($text, $pos, $filter[0]['min'] - $pos));
                        }
                        
                        for($i = 0; $i < count($filter); $i++) {
                            if($filter[$i]['max'] > $start) {
                                $array[$filter[$i]['min']] = array(
                                    'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                    'string' => array(
                                        array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['min'], $start - $filter[$i]['min']))
                                    )
                                );
                                $last = $filter[$i]['min'];
                            } else {
                                $array[$filter[$i]['min']] = array(
                                    'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                    'string' => array(
                                        array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                    )
                                );
                                
                                if($i + 1 < count($filter)) {
                                    $array[$filter[$i]['max']] = array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                } elseif($start > $filter[$i]['max']) {
                                    $array[$filter[$i]['max']] = array('type' => 'search', 'text' => mb_substr($text, $filter[$i]['max'], $start - $filter[$i]['max']));
                                }
                            }
                        }
                    } else {
                        $array[$pos] = array('type' => 'search', 'text' => mb_substr($text, $pos, $start - $pos));
                    }
                }
                
                if(mb_stripos($text, $this->search, $start) !== false && $start != mb_stripos($text, $this->search, $start)) {
                    if(isset($array[$last]) && $start > $last && $start < $last + mb_strlen($array[$last]['link'])) {
                        if(mb_stripos($text, $this->search, $start) > $last + mb_strlen($array[$last]['link'])) {
                            $array[$last]['string'][] = array('type' => 'text', 'text' => mb_substr($text, $start, $last + mb_strlen($array[$last]['link']) - $start));
                            
                            $sort = array('min' => $last + mb_strlen($array[$last]['link']), 'max' => mb_stripos($text, $this->search, $start));
                            
                            $filter = array_values(array_filter($links, function($link) use($sort) {
                                return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                            }));
                            
                            if(!empty($filter)) {
                                if($last + mb_strlen($array[$last]['link']) < $filter[0]['min']) {
                                    $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'text', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), $filter[0]['min'] - $last - mb_strlen($array[$last]['link'])));
                                }
                                
                                for($i = 0; $i < count($filter); $i++) {
                                    if($filter[$i]['max'] > mb_stripos($text, $this->search, $start)) {
                                        $array[$filter[$i]['min']] = array(
                                            'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                            'string' => array(
                                                array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], mb_stripos($text, $this->search, $start) - $filter[$i]['min']))
                                            )
                                        );
                                        $last = $filter[$i]['min'];
                                    } else {
                                        $array[$filter[$i]['min']] = array(
                                            'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                            'string' => array(
                                                array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                            )
                                        );
                                        
                                        if($i + 1 < count($filter)) {
                                            $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                        } elseif(mb_stripos($text, $this->search, $start) > $filter[$i]['max']) {
                                            $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], mb_stripos($text, $this->search, $start) - $filter[$i]['max']));
                                        }
                                    }
                                }
                            } else {
                                $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'text', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), mb_stripos($text, $this->search, $start) - $last - mb_strlen($array[$last]['link'])));
                            }
                        } else {
                            $array[$last]['string'][] = array('type' => 'text', 'text' => mb_substr($text, $start, mb_stripos($text, $this->search, $start) - $start));
                        }
                    } else {
                        $sort = array('min' => $start, 'max' => mb_stripos($text, $this->search, $start));
                        
                        $filter = array_values(array_filter($links, function($link) use($sort) {
                            return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                        }));
                        
                        if(!empty($filter)) {
                            if($start < $filter[0]['min']) {
                                $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start, $filter[0]['min'] - $start));
                            }
                            
                            for($i = 0; $i < count($filter); $i++) {
                                if($filter[$i]['max'] > mb_stripos($text, $this->search, $start)) {
                                    $array[$filter[$i]['min']] = array(
                                        'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                        'string' => array(
                                            array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], mb_stripos($text, $this->search, $start) - $filter[$i]['min']))
                                        )
                                    );
                                    $last = $filter[$i]['min'];
                                } else {
                                    $array[$filter[$i]['min']] = array(
                                        'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                        'string' => array(
                                            array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                        )
                                    );
                                    
                                    if($i + 1 < count($filter)) {
                                        $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                    } elseif(mb_stripos($text, $this->search, $start) > $filter[$i]['max']) {
                                        $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], mb_stripos($text, $this->search, $start) - $filter[$i]['max']));
                                    }
                                }
                            }
                        } else {
                            $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start, mb_stripos($text, $this->search, $start) - $start));
                        }
                    }
                } elseif(mb_stripos($text, $this->search, $start) === false && mb_strlen($text) > $start) {
                    if(isset($array[$last]) && $start > $last && $start < $last + mb_strlen($array[$last]['link'])) {
                        if(mb_strlen($text) > $last + mb_strlen($array[$last]['link'])) {
                            $array[$last]['string'][] = array('type' => 'text', 'text' => mb_substr($text, $start, $last + mb_strlen($array[$last]['link']) - $start));
                            
                            $sort = array('min' => $last + mb_strlen($array[$last]['link']), 'max' => mb_strlen($text));
                            
                            $filter = array_values(array_filter($links, function($link) use($sort) {
                                return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                            }));
                            
                            if(!empty($filter)) {
                                if($last + mb_strlen($array[$last]['link']) < $filter[0]['min']) {
                                    $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'text', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), $filter[0]['min'] - $last - mb_strlen($array[$last]['link'])));
                                }
                                
                                for($i = 0; $i < count($filter); $i++) {
                                    if($filter[$i]['max'] > mb_strlen($text)) {
                                        $array[$filter[$i]['min']] = array(
                                            'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                            'string' => array(
                                                array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], mb_strlen($text) - $filter[$i]['min']))
                                            )
                                        );
                                        $last = $filter[$i]['min'];
                                    } else {
                                        $array[$filter[$i]['min']] = array(
                                            'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                            'string' => array(
                                                array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                            )
                                        );
                                        
                                        if($i + 1 < count($filter)) {
                                            $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                        } elseif(mb_strlen($text) > $filter[$i]['max']) {
                                            $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], mb_strlen($text) - $filter[$i]['max']));
                                        }
                                    }
                                }
                            } else {
                                $array[$last + mb_strlen($array[$last]['link'])] = array('type' => 'text', 'text' => mb_substr($text, $last + mb_strlen($array[$last]['link']), mb_strlen($text) - $last - mb_strlen($array[$last]['link'])));
                            }
                        } else {
                            $array[$last]['string'][] = array('type' => 'text', 'text' => mb_substr($text, $start, mb_strlen($text) - $start));
                        }
                    } else {
                        $sort = array('min' => $start, 'max' => mb_strlen($text));
                        
                        $filter = array_values(array_filter($links, function($link) use($sort) {
                            return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
                        }));
                        
                        if(!empty($filter)) {
                            if($start < $filter[0]['min']) {
                                $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start, $filter[0]['min'] - $start));
                            }
                            
                            for($i = 0; $i < count($filter); $i++) {
                                $array[$filter[$i]['min']] = array(
                                    'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                                    'string' => array(
                                        array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                                    )
                                );
                                
                                if($i + 1 < count($filter)) {
                                    $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                                } elseif(mb_strlen($text) > $filter[$i]['max']) {
                                    $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], mb_strlen($text) - $filter[$i]['max']));
                                }
                            }
                        } else {
                            $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start));
                        }
                    }
                }
            }
        } else {
            $sort = array('min' => $start, 'max' => mb_strlen($text));
            
            $filter = array_values(array_filter($links, function($link) use($sort) {
                return $link['min'] >= $sort['min'] && $link['min'] < $sort['max'];
            }));
            
            if(!empty($filter)) {
                if($start < $filter[0]['min']) {
                    $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start, $filter[0]['min'] - $start));
                }
                
                for($i = 0; $i < count($filter); $i++) {
                    $array[$filter[$i]['min']] = array(
                        'link' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']),
                        'string' => array(
                            array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['min'], $filter[$i]['max'] - $filter[$i]['min']))
                        )
                    );
                    
                    if($i + 1 < count($filter)) {
                        $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], $filter[$i + 1]['min'] - $filter[$i]['max']));
                    } elseif(mb_strlen($text) > $filter[$i]['max']) {
                        $array[$filter[$i]['max']] = array('type' => 'text', 'text' => mb_substr($text, $filter[$i]['max'], mb_strlen($text) - $filter[$i]['max']));
                    }
                }
            } else {
                $array[$start] = array('type' => 'text', 'text' => mb_substr($text, $start));
            }
        }
        
        return $array;
    }
}
?>