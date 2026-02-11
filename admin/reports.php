<?php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Function to safely fetch count
function getCount($conn, $sql){
    $res = $conn->query($sql);
    if(!$res) die("SQL Error: ".$conn->error);
    $row = $res->fetch_assoc();
    return $row ? $row['total'] : 0;
}

// Fetch order statistics
$totalOrders  = getCount($conn, "SELECT COUNT(*) AS total FROM neworders");
$pending      = getCount($conn, "SELECT COUNT(*) AS total FROM neworders WHERE status='Pending'");
$delivered    = getCount($conn, "SELECT COUNT(*) AS total FROM neworders WHERE status='Delivered'");
$inTransit    = getCount($conn, "SELECT COUNT(*) AS total FROM neworders WHERE status='In Transit'");

// Fetch agent-wise order count
$agentOrdersSql = "SELECT agents.name, COUNT(neworders.id) AS order_count 
                   FROM agents 
                   LEFT JOIN neworders ON agents.id = neworders.agent_id 
                   GROUP BY agents.id, agents.name";
$agentOrdersRes = $conn->query($agentOrdersSql);

$agentNames = [];
$agentCounts = [];
while($row = $agentOrdersRes->fetch_assoc()){
    $agentNames[] = $row['name'];
    $agentCounts[] = (int)$row['order_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Reports - ShipManager</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { font-family:'Segoe UI', Tahoma, sans-serif; margin:0; padding:0; background:#f4f7f6; }
    .container { max-width:1100px; margin:40px auto; padding:40px; background:#fff; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.08); }
    
    h2 { text-align:center; font-size:2rem; margin-bottom:40px; color: #333; }

    /* Stats Cards */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 50px; }
    .card { background: linear-gradient(135deg, #ffffff, #f9f9f9); padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 4px solid #0077b6; transition: 0.3s; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .card h3 { font-size: 1rem; color: #666; margin: 0 0 10px 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
    .card p { font-size: 1.8rem; color: #333; margin: 0; font-weight: bold; }

    /* Chart Container */
    .chart-wrapper { 
        position: relative; 
        height: 50vh; 
        width: 100%; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
    }
</style>
</head>
<body>

<div class="container">
    <h2>📊 Analytics Dashboard</h2>

    <div class="stats">
        <div class="card" style="border-color: #0077b6;"><h3>Total Orders</h3><p><?php echo $totalOrders; ?></p></div>
        <div class="card" style="border-color: #fca311;"><h3>Pending</h3><p><?php echo $pending; ?></p></div>
        <div class="card" style="border-color: #2a9d8f;"><h3>Delivered</h3><p><?php echo $delivered; ?></p></div>
        <div class="card" style="border-color: #00b4d8;"><h3>In Transit</h3><p><?php echo $inTransit; ?></p></div>
    </div>

    <h3 style="text-align:center; color:#555; margin-bottom: 20px;">Agent Performance Distribution</h3>
    <div class="chart-wrapper">
        <canvas id="agentChart"></canvas>
    </div>
</div>

<script>
// PHP Data to JS
const labels = <?php echo json_encode($agentNames); ?>;
const dataPoints = <?php echo json_encode($agentCounts); ?>;

// Function to generate nice pastel colors dynamically
function generateColors(count) {
    const colors = [
        'rgba(255, 99, 132, 0.7)',   // Red
        'rgba(54, 162, 235, 0.7)',   // Blue
        'rgba(255, 206, 86, 0.7)',   // Yellow
        'rgba(75, 192, 192, 0.7)',   // Green
        'rgba(153, 102, 255, 0.7)',  // Purple
        'rgba(255, 159, 64, 0.7)',   // Orange
        'rgba(201, 203, 207, 0.7)',  // Grey
        'rgba(0, 119, 182, 0.7)',    // Brand Blue
        'rgba(233, 196, 106, 0.7)',  // Gold
        'rgba(42, 157, 143, 0.7)'    // Teal
    ];
    // Repeat colors if there are more agents than colors
    return Array.from({ length: count }, (_, i) => colors[i % colors.length]);
}

const ctx = document.getElementById('agentChart').getContext('2d');

new Chart(ctx, {
    type: 'polarArea', // <--- UNIQUE CHART TYPE
    data: {
        labels: labels,
        datasets: [{
            label: 'Orders Processed',
            data: dataPoints,
            backgroundColor: generateColors(labels.length),
            borderWidth: 1,
            borderColor: '#fff',
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            r: {
                ticks: { display: false }, // Hide internal numbers for cleaner look
                grid: { color: "#eee" }    // Subtle grid lines
            }
        },
        plugins: {
            legend: {
                position: 'right', // Place legend on the side
                labels: { font: { size: 14 } }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' Orders';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>