const roomCtx = document.getElementById("roomChart").getContext("2d");
const roomChart = new Chart(roomCtx, {
  type: "doughnut",
  data: {
    labels: ["Đã Thuê", "Trống", "Bảo Trì"],
    datasets: [
      {
        data: [32, 14, 2],
        backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
        borderWidth: 0,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          padding: 15,
          font: {
            size: 12,
          },
        },
      },
    },
  },
});
const revenueCtx = document.getElementById("revenueChart").getContext("2d");
let revenueChart = new Chart(revenueCtx, {
  type: "line",
  data: {
    labels: ["24/10", "25/10", "26/10", "27/10", "28/10", "29/10", "30/10"],
    datasets: [
      {
        label: "Doanh Thu (triệu VNĐ)",
        data: [12, 15, 10, 18, 14, 20, 16],
        borderColor: "#3B82F6",
        backgroundColor: "rgba(59, 130, 246, 0.1)",
        tension: 0.4,
        fill: true,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: "#e5e7eb",
        },
        ticks: {
          color: "#6b7280",
        },
      },
      x: {
        grid: {
          display: false,
        },
        ticks: {
          color: "#6b7280",
        },
      },
    },
  },
});
