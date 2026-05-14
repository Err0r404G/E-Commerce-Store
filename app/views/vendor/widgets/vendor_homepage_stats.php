<?php

class SalesPerformanceCard {
    private $title;
    private $subtitle;
    private $data30d;
    private $data90d;

    /**
     * SalesPerformanceCard Constructor
     * 
     * @param string $title - Card title (e.g., 'Sales Performance')
     * @param string $subtitle - Card subtitle (e.g., 'Last 30 days performance trend')
     * @param array $data30d - Array of data points for 30 days
     * @param array $data90d - Array of data points for 90 days
     */
    public function __construct($title, $subtitle, $data30d = [], $data90d = []) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->data30d = $data30d;
        $this->data90d = $data90d;
    }

    public function render() {
        $chartId = 'chart_' . uniqid();
        
        echo "
        <div class='sales-performance-card'>
            <div class='sales-card-header'>
                <div class='sales-card-title'>
                    <h3 class='sales-card-heading'>{$this->title}</h3>
                    <p class='sales-card-subtitle'>{$this->subtitle}</p>
                </div>
                <div class='sales-card-buttons'>
                    <button class='chart-btn active' data-period='30d'>30D</button>
                    <button class='chart-btn' data-period='90d'>90D</button>
                </div>
            </div>
            
            <div class='sales-card-body'>
                <canvas id='{$chartId}'></canvas>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('{$chartId}').getContext('2d');
            let currentPeriod = '30d';
            
            const data30d = " . json_encode($this->data30d) . ";
            const data90d = " . json_encode($this->data90d) . ";
            
            let chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data30d.labels || [],
                    datasets: [{
                        label: 'Sales',
                        data: data30d.values || [],
                        borderColor: '#1e293b',
                        backgroundColor: 'rgba(30, 41, 59, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            display: false
                        },
                        x: {
                            display: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
            
            document.querySelectorAll('.chart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentPeriod = this.dataset.period;
                    document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const newData = currentPeriod === '30d' ? data30d : data90d;
                    chart.data.labels = newData.labels || [];
                    chart.data.datasets[0].data = newData.values || [];
                    chart.update();
                });
            });
        });
        </script>
        ";
    }
}

// Usage Example:
// \$card = new SalesPerformanceCard(
//     'Sales Performance',
//     'Last 30 days performance trend',
//     [
//         'labels' => ['01 Oct', '08 Oct', '15 Oct', '22 Oct', '30 Oct'],
//         'values' => [2000, 2500, 3500, 2800, 4200]
//     ],
//     [
//         'labels' => ['01 Oct', '15 Oct', '30 Oct'],
//         'values' => [2000, 3200, 4200]
//     ]
// );
// \$card->render();
?>
