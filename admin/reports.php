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
    return $res->fetch_assoc()['total'];
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
if(!$agentOrdersRes) die("SQL Error: ".$conn->error);

$agentNames = [];
$agentCounts = [];
while($row = $agentOrdersRes->fetch_assoc()){
    $agentNames[] = $row['name'];
    $agentCounts[] = (int)$row['order_count']; // convert to int
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
body { font-family:'Segoe UI', Tahoma, Geneva, Verdana,sans-serif; margin:0; padding:0; background:#f0f4f8; }
.container { max-width:1200px; margin:40px auto; padding:30px; background:#fff; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.1);}
h2 { text-align:center; font-size:2.2rem; margin-bottom:30px; background: linear-gradient(135deg,#0077b6,#00b4d8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.stats { display:flex; justify-content:space-around; flex-wrap:wrap; gap:20px; margin-bottom:40px; }
.card { flex:1 1 200px; background:#0077b6; color:#fff; padding:25px; border-radius:15px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); transition:0.3s; }
.card:hover { transform:translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,0.15); }
.card h3 { font-size:1.5rem; margin-bottom:10px; }
.card p { font-size:1.2rem; }

.chart-container { margin-top:40px; }
canvas { background:#f8f9fa; border-radius:15px; padding:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1);}
@media(max-width:768px){ .stats{flex-direction:column; gap:15px;} }
</style>
</head>
<body>
<div class="container">
<h2>Admin Reports</h2>

<div class="stats">
    <div class="card"><h3>Total Orders</h3><p><?php echo $totalOrders; ?></p></div>
    <div class="card"><h3>Pending Orders</h3><p><?php echo $pending; ?></p></div>
    <div class="card"><h3>Delivered Orders</h3><p><?php echo $delivered; ?></p></div>
    <div class="card"><h3>In Transit</h3><p><?php echo $inTransit; ?></p></div>
</div>

<div class="chart-container">
    <h3 style="text-align:center; color:#004aad;">Orders per Agent</h3>
    <canvas id="agentChart"></canvas>
</div>

<script>
const ctx = document.getElementById('agentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($agentNames); ?>,
        datasets: [{
            label: 'Number of Orders',
            data: <?php echo json_encode($agentCounts); ?>,
            backgroundColor: 'rgba(0, 119, 182, 0.7)',
            borderColor: 'rgba(0, 119, 182, 1)',
            borderWidth: 1,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display:false }, tooltip: { mode:'index', intersect:false } },
        scales: {
            y: { beginAtZero:true, ticks:{ stepSize:1 } },
            x: { ticks:{ autoSkip:false } }
        }
    }
});
</script>
</div>
</body>
</html>
