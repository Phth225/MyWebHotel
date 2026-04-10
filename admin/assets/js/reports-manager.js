// Lấy tham số từ URL hoặc form
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        start_date: params.get('start_date') || document.getElementById('startDate')?.value || '',
        end_date: params.get('end_date') || document.getElementById('endDate')?.value || '',
        range: params.get('range') || 'month',
        period: params.get('period') || 'month'
    };
}

// Khai báo biến charts ở global scope
let revenueChart = null;
let revenuePieChart = null;
let roomTypeChart = null;
let occupancyChart = null;

// Load dữ liệu xu hướng doanh thu
function loadRevenueTrend(period = 'month') {
    const params = getUrlParams();
    fetch(`api/reports-api.php?action=revenue_trend&period=${period}&start_date=${params.start_date}&end_date=${params.end_date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success && revenueChart) {
                    revenueChart.data.labels = data.labels;
                    revenueChart.data.datasets[0].data = data.revenue;
                    revenueChart.data.datasets[1].data = data.bookings;
                    revenueChart.update();
                }
            } catch (e) {
            }
        })
        .catch(error => {
        });
}

// Load dữ liệu phân bổ doanh thu
function loadRevenueDistribution() {
    const params = getUrlParams();
    fetch(`api/reports-api.php?action=revenue_distribution&start_date=${params.start_date}&end_date=${params.end_date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success && revenuePieChart) {
                    revenuePieChart.data.datasets[0].data = [data.room, data.service];
                    revenuePieChart.update();
                }
            } catch (e) {
            }
        })
        .catch(error => {
        });
}

// Load dữ liệu doanh thu theo loại dịch vụ
function loadServiceRevenue() {
    const params = getUrlParams();
    fetch(`api/reports-api.php?action=service_revenue&start_date=${params.start_date}&end_date=${params.end_date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success && roomTypeChart && data.services.length > 0) {
                    const labels = data.services.map(s => s.name);
                    const revenues = data.services.map(s => s.revenue);
                    
                    roomTypeChart.data.labels = labels;
                    roomTypeChart.data.datasets[0].data = revenues;
                    roomTypeChart.update();
                }
            } catch (e) {
            }
        })
        .catch(error => {
        });
}

// Load dữ liệu tỷ lệ lấp đầy theo tầng
function loadOccupancyByFloor() {
    const params = getUrlParams();
    fetch(`api/reports-api.php?action=occupancy_by_floor&start_date=${params.start_date}&end_date=${params.end_date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success && occupancyChart && data.floors.length > 0) {
                    const labels = data.floors.map(f => `Tầng ${f.floor}`);
                    const occupancies = data.floors.map(f => f.occupancy);
                    
                    occupancyChart.data.labels = labels;
                    occupancyChart.data.datasets[0].data = occupancies;
                    occupancyChart.update();
                }
            } catch (e) {
            }
        })
        .catch(error => {
        });
}

// Load dữ liệu tổng quan
function loadSummary() {
    const params = getUrlParams();
    fetch(`api/reports-api.php?action=summary&start_date=${params.start_date}&end_date=${params.end_date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success && data.summary) {
                    const summary = data.summary;
                    
                    // Cập nhật title
                    const titleEl = document.getElementById('summaryTitle');
                    if (titleEl && data.date_range) {
                        const startDate = new Date(data.date_range.start_date);
                        const endDate = new Date(data.date_range.end_date);
                        const startStr = startDate.toLocaleDateString('vi-VN');
                        const endStr = endDate.toLocaleDateString('vi-VN');
                        titleEl.textContent = `Tổng Quan ${startStr} - ${endStr}`;
                    }
                    
                    // Cập nhật doanh thu
                    const revenueEl = document.getElementById('summaryRevenue');
                    if (revenueEl) {
                        revenueEl.textContent = (summary.total_revenue / 1000000).toFixed(1) + 'M';
                    }
                    
                    // Cập nhật số booking
                    const bookingsEl = document.getElementById('summaryBookings');
                    if (bookingsEl) {
                        bookingsEl.textContent = summary.total_bookings.toLocaleString('vi-VN');
                    }
                    
                    // Cập nhật tỷ lệ lấp đầy
                    const occupancyEl = document.getElementById('summaryOccupancy');
                    if (occupancyEl) {
                        occupancyEl.textContent = summary.occupancy_rate.toFixed(1) + '%';
                    }
                    
                    // Cập nhật đánh giá
                    const ratingEl = document.getElementById('summaryRating');
                    if (ratingEl) {
                        ratingEl.textContent = summary.avg_rating.toFixed(1);
                    }
                }
            } catch (e) {
            }
        })
        .catch(error => {
        });
}

// Khởi tạo charts khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Revenue & Booking Trend Chart
    const revenueCtx = document.getElementById("revenueChart")?.getContext("2d");
    
    if (revenueCtx) {
        revenueChart = new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: [],
                datasets: [
                    {
                        label: "Doanh Thu (triệu VNĐ)",
                        data: [],
                        borderColor: "#2196F3",
                        backgroundColor: "rgba(33, 150, 243, 0.1)",
                        tension: 0.4,
                        fill: true,
                        yAxisID: "y",
                    },
                    {
                        label: "Số Đặt Phòng",
                        data: [],
                        borderColor: "#4CAF50",
                        backgroundColor: "rgba(76, 175, 80, 0.1)",
                        tension: 0.4,
                        fill: true,
                        yAxisID: "y1",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: "top",
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed.y !== null) {
                                    if (context.datasetIndex === 0) {
                                        label += context.parsed.y + " triệu VNĐ";
                                    } else {
                                        label += context.parsed.y + " đặt phòng";
                                    }
                                }
                                return label;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        type: "linear",
                        display: true,
                        position: "left",
                        title: {
                            display: true,
                            text: "Doanh thu (triệu VNĐ)",
                        },
                    },
                    y1: {
                        type: "linear",
                        display: true,
                        position: "right",
                        title: {
                            display: true,
                            text: "Số lần đặt",
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
            },
        });
        
        // Load dữ liệu ban đầu
        loadRevenueTrend('month');
    }
    
    // Load dữ liệu tổng quan ban đầu
    loadSummary();

    // Biểu đồ phân bổ doanh thu (Phòng vs Dịch vụ)
    const ctxRevenue = document.getElementById("revenuePieChart")?.getContext("2d");
    
    if (ctxRevenue) {
        revenuePieChart = new Chart(ctxRevenue, {
          type: "doughnut",
          data: {
            labels: ["Phòng", "Dịch vụ"],
            datasets: [
              {
                data: [0, 0],
                backgroundColor: [
                  "rgba(54, 162, 235, 0.8)", // Phòng
                  "rgba(255, 99, 132, 0.8)", // Dịch vụ
                ],
                borderColor: ["rgba(54, 162, 235, 1)", "rgba(255, 99, 132, 1)"],
                borderWidth: 1,
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
                  boxWidth: 20,
                  color: "#444",
                },
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((a, b) => a + b, 0);
                    const value = dataset.data[context.dataIndex];
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + "%" : "0%";
                    return `${context.label}: ${value.toLocaleString('vi-VN')} VNĐ (${percentage})`;
                  },
                },
              },
            },
            cutout: "50%",
          },
        });
        
        // Load dữ liệu ban đầu
        loadRevenueDistribution();
    }

    // Doanh thu theo loại dịch vụ
    const ctxRoom = document.getElementById("roomTypeChart")?.getContext("2d");
    
    if (ctxRoom) {
        roomTypeChart = new Chart(ctxRoom, {
          type: "bar",
          data: {
            labels: [],
            datasets: [
              {
                label: "Doanh thu (VNĐ)",
                data: [],
                backgroundColor: [
                  "rgba(255, 206, 86, 0.8)",
                  "rgba(75, 192, 192, 0.8)",
                  "rgba(153, 102, 255, 0.8)",
                  "rgba(255, 159, 64, 0.8)",
                  "rgba(54, 162, 235, 0.8)",
                ],
                borderColor: [
                  "rgba(255, 206, 86, 1)",
                  "rgba(75, 192, 192, 1)",
                  "rgba(153, 102, 255, 1)",
                  "rgba(255, 159, 64, 1)",
                  "rgba(54, 162, 235, 1)",
                ],
                borderWidth: 1,
                borderRadius: 6,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function (value) {
                    return value.toLocaleString("vi-VN") + "₫";
                  },
                },
                title: {
                  display: true,
                  text: "Doanh thu (VNĐ)",
                  color: "#333",
                  font: { weight: "bold" },
                },
              },
              x: {
                title: {
                  display: true,
                  text: "Loại dịch vụ",
                  color: "#333",
                  font: { weight: "bold" },
                },
              },
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    return `${context.label}: ${context.parsed.y.toLocaleString(
                      "vi-VN"
                    )}₫`;
                  },
                },
              },
            },
          },
        });
        
        // Load dữ liệu ban đầu
        loadServiceRevenue();
    }

    // Occupancy Chart
    const occupancyCtx = document.getElementById("occupancyChart")?.getContext("2d");
    
    if (occupancyCtx) {
        occupancyChart = new Chart(occupancyCtx, {
          type: "bar",
          data: {
            labels: [],
            datasets: [
              {
                label: "Tỷ lệ lấp đầy (%)",
                data: [],
                backgroundColor: "rgba(33, 150, 243, 0.6)",
                borderColor: "#2196F3",
                borderWidth: 2,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              x: {
                beginAtZero: true,
                max: 100,
                title: {
                  display: true,
                  text: "Tỷ lệ (%)",
                },
              },
            },
          },
        });
        
        // Load dữ liệu ban đầu
        loadOccupancyByFloor();
    }

    // Update date range
    const startDateEl = document.getElementById("startDate");
    const endDateEl = document.getElementById("endDate");

    if (startDateEl) {
        startDateEl.addEventListener("change", function () {
            reloadReports();
        });
    }

    if (endDateEl) {
        endDateEl.addEventListener("change", function () {
            reloadReports();
        });
    }
});

// Functions
function setTimeRange(range, buttonElement) {
  // Remove active class from all buttons
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  // Add active class to clicked button
  if (buttonElement) {
    buttonElement.classList.add("active");
  }

  // Reload page with new range
  const url = new URL(window.location);
  url.searchParams.set('range', range);
  window.location.href = url.toString();
}

function updateRevenueChart(period, buttonElement) {
  // Remove active class from all chart buttons
  document.querySelectorAll(".chart-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  if (buttonElement) {
    buttonElement.classList.add("active");
  }

  // Load dữ liệu từ API
  loadRevenueTrend(period);
}

function exportReport() {
    const params = getUrlParams();
    const exportUrl = `api/reports-api.php?action=export&start_date=${params.start_date}&end_date=${params.end_date}&format=excel`;
    
    // Mở link xuất báo cáo trong tab mới để download
    window.open(exportUrl, '_blank');
}

// Reload tất cả báo cáo khi thay đổi date range
function reloadReports() {
    const startDateEl = document.getElementById("startDate");
    const endDateEl = document.getElementById("endDate");
    const startDate = startDateEl?.value || '';
    const endDate = endDateEl?.value || '';
    
    if (startDate && endDate) {
        // Reload tất cả dữ liệu động
        loadSummary();
        loadRevenueTrend('month');
        loadRevenueDistribution();
        loadServiceRevenue();
        loadOccupancyByFloor();
        
        // Cập nhật URL không reload trang
        const url = new URL(window.location);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        window.history.pushState({}, '', url.toString());
    }
}
