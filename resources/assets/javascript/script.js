var labels = [];
let detectedFaces = [];
let videoStream = null;
let modelsLoaded = false;
let webcamStarted = false;
let detectionInterval = null;

// Load models once on page load
Promise.all([
  faceapi.nets.ssdMobilenetv1.loadFromUri("models"),
  faceapi.nets.faceRecognitionNet.loadFromUri("models"),
  faceapi.nets.faceLandmark68Net.loadFromUri("models"),
])
  .then(() => {
    modelsLoaded = true;
    console.log("Models loaded successfully");
  })
  .catch((err) => {
    console.error("Model loading failed:", err);
    alert("Models not loaded, please check your model folder location");
  });

// Update student table when dropdowns change
function updateTable() {
  var selectedCourseID = document.getElementById("courseSelect").value;
  var selectedUnitCode = document.getElementById("unitSelect").value;
  var selectedVenue = document.getElementById("venueSelect").value;

  if (!selectedCourseID || !selectedUnitCode || !selectedVenue) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "resources/pages/lecture/manageFolder.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        var response = JSON.parse(xhr.responseText);
        if (response.status === "success") {
          labels = response.data;
          document.getElementById("studentTableContainer").innerHTML = response.html;
        } else {
          console.error("Error:", response.message);
        }
      } catch (e) {
        console.error("Failed to parse response:", e);
      }
    }
  };

  xhr.send(
    "courseID=" + encodeURIComponent(selectedCourseID) +
    "&unitID=" + encodeURIComponent(selectedUnitCode) +
    "&venueID=" + encodeURIComponent(selectedVenue)
  );
}

// Mark attendance in table
function markAttendance(recognized) {
  document.querySelectorAll("#studentTableContainer tr").forEach((row) => {
    if (!row.cells || row.cells.length < 6) return;
    const regNo = row.cells[0].innerText.trim();
    if (recognized.includes(regNo)) {
      row.cells[5].innerText = "present";
    }
  });
}

// Start webcam
function startWebcam() {
  const video = document.getElementById("video");
  navigator.mediaDevices
    .getUserMedia({ video: true, audio: false })
    .then((stream) => {
      video.srcObject = stream;
      videoStream = stream;
    })
    .catch((error) => {
      console.error("Webcam error:", error);
      alert("Could not access camera: " + error.message);
    });
}

// Load labeled face descriptors from stored images
async function getLabeledFaceDescriptions() {
  const labeledDescriptors = [];
  for (const label of labels) {
    const descriptions = [];
    for (let i = 1; i <= 5; i++) {
      try {
        const img = await faceapi.fetchImage(`resources/labels/${label}/${i}.png`);
        const detection = await faceapi
          .detectSingleFace(img)
          .withFaceLandmarks()
          .withFaceDescriptor();
        if (detection) {
          descriptions.push(detection.descriptor);
        }
      } catch (error) {
        console.warn(`Skipping ${label}/${i}.png:`, error.message);
      }
    }
    if (descriptions.length > 0) {
      labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(label, descriptions));
    }
  }
  return labeledDescriptors;
}

// Launch button click
document.getElementById("startButton").addEventListener("click", async () => {
  if (!modelsLoaded) {
    alert("Please wait, models are still loading...");
    return;
  }

  const selectedCourse = document.getElementById("courseSelect").value;
  const selectedUnit = document.getElementById("unitSelect").value;
  const selectedVenue = document.getElementById("venueSelect").value;

  if (!selectedCourse || !selectedUnit || !selectedVenue) {
    alert("Please select course, unit, and venue first.");
    return;
  }

  const videoContainer = document.querySelector(".video-container");
  const video = document.getElementById("video");

  videoContainer.style.display = "flex";

  if (!webcamStarted) {
    startWebcam();
    webcamStarted = true;
  }

  // Wait for video to play then start detection
  video.addEventListener("play", async function onPlay() {
    video.removeEventListener("play", onPlay); // prevent duplicate listeners

    const labeledFaceDescriptors = await getLabeledFaceDescriptions();

    if (labeledFaceDescriptors.length === 0) {
      console.warn("No face descriptors loaded. Recognition will not work.");
    }

    const faceMatcher = labeledFaceDescriptors.length > 0
      ? new faceapi.FaceMatcher(labeledFaceDescriptors)
      : null;

    // Use the existing canvas from HTML
    const canvas = document.getElementById("overlay");
    const displaySize = { width: video.width, height: video.height };
    faceapi.matchDimensions(canvas, displaySize);

    // Clear any previous interval
    if (detectionInterval) clearInterval(detectionInterval);

    detectionInterval = setInterval(async () => {
      const detections = await faceapi
        .detectAllFaces(video)
        .withFaceLandmarks()
        .withFaceDescriptors();

      const resizedDetections = faceapi.resizeResults(detections, displaySize);
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      if (!faceMatcher || resizedDetections.length === 0) return;

      const results = resizedDetections.map((d) =>
        faceMatcher.findBestMatch(d.descriptor)
      );

      // Update attendance table
      detectedFaces = results
        .map((r) => r.label)
        .filter((l) => l !== "unknown");
      markAttendance(detectedFaces);

      // Draw boxes and ID badges
      results.forEach((result, i) => {
        const box = resizedDetections[i].detection.box;
        const label = result.label;
        const isKnown = label !== "unknown";

        const x = Math.round(box.x);
        const y = Math.round(box.y);
        const w = Math.round(box.width);
        const h = Math.round(box.height);

        // Face bounding box
        ctx.strokeStyle = isKnown ? "#00E676" : "#FF1744";
        ctx.lineWidth = 2.5;
        ctx.strokeRect(x, y, w, h);

        // ID badge above the face
        const displayText = isKnown ? label : "Unknown";
        const fontSize = 14;
        ctx.font = `bold ${fontSize}px Arial, sans-serif`;

        const textWidth = ctx.measureText(displayText).width;
        const padX = 10;
        const padY = 6;
        const badgeW = textWidth + padX * 2;
        const badgeH = fontSize + padY * 2;
        const badgeX = x + w / 2 - badgeW / 2;
        const badgeY = y - badgeH - 6;

        // Badge background
        ctx.fillStyle = isKnown ? "#00E676" : "#FF1744";
        if (ctx.roundRect) {
          ctx.beginPath();
          ctx.roundRect(badgeX, badgeY, badgeW, badgeH, 6);
          ctx.fill();
        } else {
          ctx.fillRect(badgeX, badgeY, badgeW, badgeH);
        }

        // Badge text
        ctx.fillStyle = isKnown ? "#0D1B2A" : "#ffffff";
        ctx.fillText(displayText, badgeX + padX, badgeY + badgeH - padY);
      });
    }, 100);
  });
});

// END Attendance button
document.getElementById("endAttendance").addEventListener("click", function () {
  sendAttendanceDataToServer();

  if (detectionInterval) {
    clearInterval(detectionInterval);
    detectionInterval = null;
  }

  const canvas = document.getElementById("overlay");
  if (canvas) {
    canvas.getContext("2d").clearRect(0, 0, canvas.width, canvas.height);
  }

  document.querySelector(".video-container").style.display = "none";
  stopWebcam();
  webcamStarted = false;
});

function stopWebcam() {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
    const video = document.getElementById("video");
    video.srcObject = null;
    videoStream = null;
  }
}

function sendAttendanceDataToServer() {
  const attendanceData = [];
  document.querySelectorAll("#studentTableContainer tr").forEach((row, index) => {
    if (index === 0 || !row.cells || row.cells.length < 6) return;
    attendanceData.push({
      studentID: row.cells[0].innerText.trim(),
      course: row.cells[2].innerText.trim(),
      unit: row.cells[3].innerText.trim(),
      attendanceStatus: row.cells[5].innerText.trim(),
    });
  });

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "handle_attendance", true);
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          showMessage(
            response.message ||
              (response.status === "success"
                ? "Attendance recorded successfully."
                : "An error occurred while recording attendance.")
          );
        } catch (e) {
          showMessage("Error: Failed to parse server response.");
        }
      } else {
        showMessage("Error: HTTP Status " + xhr.status);
      }
    }
  };

  xhr.send(JSON.stringify(attendanceData));
}

function showMessage(message) {
  var messageDiv = document.getElementById("messageDiv");
  messageDiv.style.display = "block";
  messageDiv.innerHTML = message;
  messageDiv.style.opacity = 1;
  setTimeout(function () {
    messageDiv.style.opacity = 0;
  }, 5000);
}
