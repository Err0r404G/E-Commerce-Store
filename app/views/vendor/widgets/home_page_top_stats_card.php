<?php

class StatsCard {
    private $icon;
    private $label;
    private $value;
    private $percentage;
    private $isPositive;

    public function __construct($icon, $label, $value, $percentage, $isPositive = true) {
        $this->icon = $icon;
        $this->label = $label;
        $this->value = $value;
        $this->percentage = $percentage;
        $this->isPositive = $isPositive;
    }

    public function render() {
        $percentageClass = $this->isPositive ? 'positive' : 'negative';
        $percentageSymbol = $this->isPositive ? '+' : '';
        
        echo "
        <div class='stats-card'>
            <div class='stats-card-header'>
                <div class='stats-card-icon'>
                    <i class='{$this->icon}'></i>
                </div>
                <div class='stats-card-percentage {$percentageClass}'>
                    {$percentageSymbol}{$this->percentage}%
                </div>
            </div>
            
            <div class='stats-card-body'>
                <p class='stats-card-label'>{$this->label}</p>
                <p class='stats-card-value'>{$this->value}</p>
            </div>
        </div>
        ";
    }
}

// Usage Example:
// $card = new StatsCard('fas fa-shopping-bag', 'Total Sales', '$42,980.50', 12.5, true);
// $card->render();
?>
