const video = document.getElementById('video');
const canvas = faceapi.createCanvasFromMedia(video);
document.body.append(canvas);
const displaySize = { width: video.width, height: video.height };
faceapi.matchDimensions(canvas, displaySize);

// Access the webcam
navigator.mediaDevices.getUserMedia({
    video: true
}).then(stream => {
    video.srcObject = stream;
}).catch(err => {
    console.error("Error accessing webcam:", err);
});

// Load the models from the local models folder
async function loadModels() {
    // Load the models from the local 'models' directory
    await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
    await faceapi.nets.faceExpressionNet.loadFromUri('models');
    startDetection(); // Start detecting faces after models are loaded
}

// Start the smile detection process
async function startDetection() {
    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(video).withFaceExpressions();
        canvas.clear();
        const resizedDetections = faceapi.resizeResults(detections, displaySize);
        canvas.drawDetections(resizedDetections);
        canvas.drawFaceExpressions(resizedDetections);

        const smile = detections[0]?.expressions?.happy;
        
        if (smile > 0.5) {
            document.getElementById('message').textContent = "😊 You smiled!";
        } else {
            document.getElementById('message').textContent = "";
        }
    }, 100);
}

// Load the models and start detection
loadModels();
