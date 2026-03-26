var labels = [];
let detectedFaces = [];
let videoStream = null;
let modelsLoaded = false;
let webcamStarted = false;
let detectionInterval = null;
let reCheckInterval = null;
let countdownInterval = null;
let faceMatcher = null;
let displaySize = null;

const THIRTY_MINUTES_MS = 30 * 60 * 1000;
let reCheckSecondsLeft = 30 * 60;

// ── Load models once on page load
Promise.all([
  faceapi.nets.ssdMobilenetv1.loadFromUri("models"),
  faceapi.nets.tinyFaceDetector.loadFromUri("models"),
  faceapi.nets.faceRecognitionNet.loadFromUri("models"),
  faceapi.nets.faceLandmark68Net.loadFromUri("models"),
])
  .then(() => { modelsLoaded = true; console.log("Models loaded successfully"); })
  .catch((err) => { console.error("Model loading failed:", err); alert("Models not loaded, please check your model folder location"); });

// ── Update student table
function updateTable() {
  var selectedCourseID = document.getElementById("courseSelect").value;
  var selectedUnitCode = document.getElementById("unitSelect").value;
  var selectedVenue    = document.getElementById("venueSelect").value;
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
        } else { console.error("Error:", response.message); }
      } catch (e) { console.error("Failed to parse response:", e); }
    }
  };
  xhr.send("courseID=" + encodeURIComponent(selectedCourseID) + "&unitID=" + encodeURIComponent(selectedUnitCode) + "&venueID=" + encodeURIComponent(selectedVenue));
}

// ── Mark students present
function markAttendance(recognized) {
  document.querySelectorAll("#attendanceTableBody tr").forEach((row) => {
    if (!row.cells || row.cells.length < 6) return;
    const regNo = row.cells[0].innerText.trim();
    if (recognized.includes(regNo)) {
      row.cells[5].innerText = "Present";
      row.cells[5].style.color = "#00c853";
      row.cells[5].style.fontWeight = "bold";
    }
  });
}

// ── Start webcam
function startWebcam() {
  const video = document.getElementById("video");
  navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    .then((stream) => { video.srcObject = stream; videoStream = stream; })
    .catch((error) => { console.error("Webcam error:", error); alert("Could not access camera: " + error.message); });
}

// ── Load labeled face descriptors — crops face region for better descriptor accuracy
async function getLabeledFaceDescriptions() {
  const labeledDescriptors = [];
  console.log("Loading face descriptors for", labels.length, "student(s):", labels);

  for (const label of labels) {
    const descriptions = [];

    for (let i = 1; i <= 5; i++) {
      try {
        const img = await faceapi.fetchImage(`resources/labels/${label}/${i}.png`);

        // Step 1: detect face in the full image
        const detection = await faceapi
          .detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
          .withFaceLandmarks()
          .withFaceDescriptor();

        if (detection) {
          descriptions.push(detection.descriptor);
          console.log(`✔ [${label}] image ${i} — face detected, descriptor extracted`);
        } else {
          // Fallback: try TinyFaceDetector if SSD missed it
          const fallback = await faceapi
            .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

          if (fallback) {
            descriptions.push(fallback.descriptor);
            console.log(`✔ [${label}] image ${i} — face detected via fallback detector`);
          } else {
            console.warn(`✘ [${label}] image ${i} — no face detected, skipping`);
          }
        }
      } catch (err) {
        console.warn(`✘ [${label}] image ${i} error:`, err.message);
      }
    }

    if (descriptions.length > 0) {
      console.log(`✅ [${label}] loaded ${descriptions.length}/5 descriptors`);
      labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(label, descriptions));
    } else {
      console.warn(`❌ [${label}] — 0 descriptors loaded. Check resources/labels/${label}/`);
    }
  }

  console.log(`Total: ${labeledDescriptors.length}/${labels.length} students have face data`);
  return labeledDescriptors;
}

// ── Get student name from table
function getStudentName(regNo) {
  let found = null;
  document.querySelectorAll("#attendanceTableBody tr").forEach((row) => {
    if (row.cells && row.cells.length >= 2 && row.cells[0].innerText.trim() === regNo)
      found = row.cells[1].innerText.trim();
  });
  return found || regNo;
}

// ── Draw face box with name ABOVE (like YouTube screenshot style)
function drawFaceBox(ctx, box, displayText, isKnown) {
  const x = Math.round(box.x), y = Math.round(box.y), w = Math.round(box.width), h = Math.round(box.height);

  // Colors: green for known, red for unknown
  const boxColor   = isKnown ? "#00E676" : "#FF1744";
  const accentColor = isKnown ? "#69FF47" : "#FF6D00";

  // Main bounding box
  ctx.strokeStyle = boxColor;
  ctx.lineWidth = 2.5;
  ctx.strokeRect(x, y, w, h);

  // Corner accents
  const cLen = Math.min(w, h) * 0.18;
  ctx.strokeStyle = accentColor;
  ctx.lineWidth = 4;
  ctx.beginPath(); ctx.moveTo(x, y + cLen); ctx.lineTo(x, y); ctx.lineTo(x + cLen, y); ctx.stroke();
  ctx.beginPath(); ctx.moveTo(x + w - cLen, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w, y + cLen); ctx.stroke();
  ctx.beginPath(); ctx.moveTo(x, y + h - cLen); ctx.lineTo(x, y + h); ctx.lineTo(x + cLen, y + h); ctx.stroke();
  ctx.beginPath(); ctx.moveTo(x + w - cLen, y + h); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w, y + h - cLen); ctx.stroke();

  // Name label ABOVE the box (anchored to top edge)
  const fontSize = Math.max(13, Math.min(16, Math.round(w * 0.085)));
  ctx.font = `bold ${fontSize}px Arial, sans-serif`;
  const textWidth = ctx.measureText(displayText).width;
  const padX = 10, padY = 5;
  const badgeW = textWidth + padX * 2;
  const badgeH = fontSize + padY * 2;
  const badgeX = x;                    // left-aligned with the box (like the screenshot)
  const badgeY = y - badgeH;           // sits directly above the top edge

  ctx.fillStyle = boxColor;
  if (ctx.roundRect) {
    ctx.beginPath();
    ctx.roundRect(badgeX, badgeY, badgeW, badgeH, [4, 4, 0, 0]); // rounded top corners only
    ctx.fill();
  } else {
    ctx.fillRect(badgeX, badgeY, badgeW, badgeH);
  }

  ctx.fillStyle = isKnown ? "#0D1B2A" : "#ffffff";
  ctx.textBaseline = "middle";
  ctx.fillText(displayText, badgeX + padX, badgeY + badgeH / 2);
  ctx.textBaseline = "alphabetic";
}

// ── Continuous detection loop — uses a busy flag to prevent overlapping async calls
let isDetecting = false;

function startDetectionLoop() {
  const video  = document.getElementById("video");
  const canvas = document.getElementById("overlay");
  if (detectionInterval) clearInterval(detectionInterval);

  // Use 300ms interval — gives enough time for multi-face descriptor extraction to finish
  detectionInterval = setInterval(async () => {
    // Skip this tick if previous detection is still running
    if (isDetecting) return;
    if (!displaySize)  return;
    if (video.readyState < 2 || video.videoWidth === 0) return;

    isDetecting = true;
    try {
      // Sync canvas size if video dimensions changed
      if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.style.width  = video.videoWidth + "px";
        canvas.style.height = video.videoHeight + "px";
        displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);
      }

      // Detect ALL faces — minConfidence 0.3 catches side-angle/smaller faces in a group
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
        .withFaceLandmarks()
        .withFaceDescriptors();

      const resized = faceapi.resizeResults(detections, displaySize);
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      if (resized.length === 0) { isDetecting = false; return; }

      if (faceMatcher) {
        // Each face is matched independently — ALL faces in frame get their own box & name
        resized.forEach((d) => {
          const result  = faceMatcher.findBestMatch(d.descriptor);
          const label   = result.label;
          const isKnown = label !== "unknown";
          // Mark attendance once per student (stays marked even if they look away)
          if (isKnown && !detectedFaces.includes(label)) {
            detectedFaces.push(label);
            markAttendance([label]);
          }
          drawFaceBox(ctx, d.detection.box, isKnown ? getStudentName(label) : "Unknown", isKnown);
        });
      } else {
        resized.forEach((d) => {
          drawFaceBox(ctx, d.detection.box, "Unknown", false);
        });
      }
    } catch (err) {
      console.error("Detection error:", err);
    } finally {
      isDetecting = false;
    }
  }, 300);
}

// ── 30-minute countdown ticker
function startCountdown() {
  if (countdownInterval) clearInterval(countdownInterval);
  reCheckSecondsLeft = 30 * 60;
  const countdownEl = document.getElementById("reCheckCountdown");
  const barEl       = document.getElementById("reCheckBar");
  const statusEl    = document.getElementById("reCheckStatus");
  countdownInterval = setInterval(() => {
    reCheckSecondsLeft--;
    if (reCheckSecondsLeft < 0) reCheckSecondsLeft = 30 * 60;
    const mins = Math.floor(reCheckSecondsLeft / 60);
    const secs = reCheckSecondsLeft % 60;
    if (countdownEl) countdownEl.textContent = `${String(mins).padStart(2,"0")}:${String(secs).padStart(2,"0")}`;
    const pct = (reCheckSecondsLeft / (30 * 60)) * 100;
    if (barEl) {
      barEl.style.width = pct + "%";
      if (reCheckSecondsLeft <= 60) barEl.style.background = "#ef4444";
      else if (reCheckSecondsLeft <= 300) barEl.style.background = "#f59e0b";
      else barEl.style.background = "#10b981";
    }
    if (statusEl && statusEl.className !== "recheck-status checking") {
      statusEl.textContent = "Monitoring…";
      statusEl.className = "recheck-status active";
    }
  }, 1000);
}

// ── Every 30 minutes: re-check attendance
function startReCheckTimer() {
  if (reCheckInterval) clearInterval(reCheckInterval);
  const panel = document.getElementById("reCheckPanel");
  if (panel) panel.style.display = "block";
  startCountdown();
  reCheckInterval = setInterval(async () => {
    const statusEl = document.getElementById("reCheckStatus");
    console.log("30-minute re-check triggered");
    showMessage("⏱ 30 minutes reached — automatically re-checking attendance…");
    if (statusEl) { statusEl.textContent = "Re-checking…"; statusEl.className = "recheck-status checking"; }

    // Reset all to Absent
    document.querySelectorAll("#attendanceTableBody tr").forEach((row) => {
      if (row.cells && row.cells.length >= 6) {
        row.cells[5].innerText = "Absent";
        row.cells[5].style.color = "";
        row.cells[5].style.fontWeight = "";
      }
    });

    const video = document.getElementById("video");
    if (faceMatcher && displaySize && video) {
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
        .withFaceLandmarks()
        .withFaceDescriptors();
      const resized = faceapi.resizeResults(detections, displaySize);
      if (resized.length > 0) {
        const present = [];
        resized.forEach((d) => {
          const result = faceMatcher.findBestMatch(d.descriptor);
          if (result.label !== "unknown" && !present.includes(result.label)) {
            present.push(result.label);
          }
        });
        markAttendance(present);
        showMessage(`✅ Re-check complete! ${present.length} student(s) confirmed present.`);
        console.log("Re-check done. Present:", present);
      } else {
        showMessage("⚠ Re-check complete — no faces detected at this moment.");
      }
    }

    if (statusEl) {
      statusEl.textContent = "Done ✓"; statusEl.className = "recheck-status done";
      setTimeout(() => { statusEl.textContent = "Monitoring…"; statusEl.className = "recheck-status active"; }, 3000);
    }
    startCountdown();
  }, THIRTY_MINUTES_MS);
}

// ── Launch button
document.getElementById("startButton").addEventListener("click", async () => {
  if (!modelsLoaded) { alert("Please wait, models are still loading..."); return; }

  const selectedCourse = document.getElementById("courseSelect").value;
  const selectedUnit   = document.getElementById("unitSelect").value;
  const selectedVenue  = document.getElementById("venueSelect").value;
  if (!selectedCourse || !selectedUnit || !selectedVenue) {
    alert("Please select course, unit, and venue first.");
    return;
  }

  // Re-fetch latest student labels fresh from the server before launching
  // This guarantees labels are up-to-date even if updateTable() was never triggered
  await new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "resources/pages/lecture/manageFolder.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.status === "success") {
            labels = response.data;
            document.getElementById("studentTableContainer").innerHTML = response.html;
          } else {
            console.error("Error fetching labels:", response.message);
          }
        } catch (e) { console.error("Failed to parse label response:", e); }
        resolve();
      }
    };
    xhr.onerror = () => resolve();
    xhr.send(
      "courseID=" + encodeURIComponent(selectedCourse) +
      "&unitID="  + encodeURIComponent(selectedUnit)   +
      "&venueID=" + encodeURIComponent(selectedVenue)
    );
  });

  if (!labels || labels.length === 0) {
    alert("No registered students found for this course/unit/venue. Please register students first.");
    return;
  }

  // Show loading status
  showMessage("⏳ Loading face data for " + labels.length + " student(s), please wait…");

  const videoContainer = document.getElementById("videoContainer");
  const video = document.getElementById("video");
  videoContainer.style.display = "block";

  // Start webcam first so it warms up while we load descriptors
  if (!webcamStarted) { startWebcam(); webcamStarted = true; }

  // Load all labeled face descriptors with console progress
  const labeledFaceDescriptors = await getLabeledFaceDescriptions();

  if (labeledFaceDescriptors.length === 0) {
    showMessage("⚠ No face images found for the registered students. Make sure student photos are saved in resources/labels/{registrationNo}/1.png … 5.png");
    console.warn("No face descriptors loaded — check resources/labels/ folder.");
  } else {
    showMessage("✅ Face data loaded for " + labeledFaceDescriptors.length + " student(s). Starting recognition…");
  }

  faceMatcher = labeledFaceDescriptors.length > 0
    ? new faceapi.FaceMatcher(labeledFaceDescriptors, 0.6)  // 0.6 = face-api default, best for varied conditions
    : null;

  async function initRecognition() {
    const vw = video.videoWidth  || 640;
    const vh = video.videoHeight || 480;
    video.width  = vw;
    video.height = vh;

    const canvas = document.getElementById("overlay");
    canvas.width  = vw;
    canvas.height = vh;
    canvas.style.width  = vw + "px";
    canvas.style.height = vh + "px";

    // Ensure canvas is inside videoContainer for correct absolute positioning
    const container = document.getElementById("videoContainer");
    if (canvas.parentElement !== container) {
      container.appendChild(canvas);
    }

    displaySize = { width: vw, height: vh };
    faceapi.matchDimensions(canvas, displaySize);
    startDetectionLoop();
    startReCheckTimer();
  }

  // Wait for webcam to be ready before starting detection
  if (video.readyState >= 2 && video.videoWidth > 0) {
    await initRecognition();
  } else {
    video.addEventListener("loadeddata", async function onReady() {
      video.removeEventListener("loadeddata", onReady);
      await initRecognition();
    });
  }
});

// ── End Attendance button
document.getElementById("endAttendance").addEventListener("click", function () {
  sendAttendanceDataToServer();
  if (detectionInterval)  { clearInterval(detectionInterval);  detectionInterval = null; }
  if (reCheckInterval)    { clearInterval(reCheckInterval);    reCheckInterval = null; }
  if (countdownInterval)  { clearInterval(countdownInterval);  countdownInterval = null; }
  const canvas = document.getElementById("overlay");
  if (canvas) canvas.getContext("2d").clearRect(0, 0, canvas.width, canvas.height);
  const panel = document.getElementById("reCheckPanel");
  if (panel) panel.style.display = "none";
  document.getElementById("videoContainer").style.display = "none";
  stopWebcam();
  webcamStarted = false; faceMatcher = null; displaySize = null; isDetecting = false; detectedFaces = [];
});

function stopWebcam() {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
    document.getElementById("video").srcObject = null;
    videoStream = null;
  }
}

// ── Send attendance to server
function sendAttendanceDataToServer() {
  const attendanceData = [];
  document.querySelectorAll("#attendanceTableBody tr").forEach((row) => {
    if (!row.cells || row.cells.length < 6) return;
    attendanceData.push({
      studentID: row.cells[0].innerText.trim(), name: row.cells[1].innerText.trim(),
      course: row.cells[2].innerText.trim(), unit: row.cells[3].innerText.trim(),
      venue: row.cells[4].innerText.trim(), attendanceStatus: row.cells[5].innerText.trim() || "Absent",
    });
  });
  if (attendanceData.length === 0) { showMessage("No student data to record."); return; }
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "resources/pages/lecture/handle_attendance.php", true);
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          showMessage(response.message || (response.status === "success" ? "Attendance recorded successfully." : "An error occurred."));
        } catch (e) { showMessage("Error: Failed to parse server response."); }
      } else { showMessage("Error: HTTP Status " + xhr.status); }
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
    setTimeout(() => { messageDiv.style.display = "none"; }, 500);
  }, 5000);
}