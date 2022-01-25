var fps = 60;

var Timer = function(obj){
    this.time = obj.time;
    this.fps = obj.fps;
    this.onEnd = obj.onEnd || null;
    this.onStart = obj.onStart || null;
    this.onTick = obj.onTick || null;
    this.intervalID = null;

    this.start = () => {
        this.interval = setInterval(this.update, 1000 / this.fps);
        this.onStart ? this.onStart() : void 0;
        return this;
    };
    this.stop = () => {
        clearInterval(this.interval);
        this.onEnd ? this.onEnd() : void 0;
    };
    this.update = () => {
        this.time > 0 ? this.time -= 1/this.fps : this.stop();
        this.onTick ? this.onTick() : void 0;
        return this.get();
    }
    this.get = (par) => {
        switch(par) {
            case undefined:
                return this.time;
            case "dig":
                let hours = Math.floor(this.time / 60 / 60);
                let minutes = Math.floor(this.time / 60) - (hours * 60);
                let seconds = Math.floor(this.time % 60);
                return formatted = [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0')
                ].join(':');
            case "end":
                return this.onEnd();
        }
    }
}

var timer1 = new Timer({
    time: time,
    fps: fps,
    onTick: tick,
    onEnd: endTimer,
    onStart: onTimerStart
});

function onTimerStart(){
}
function endTimer(){
    document.location.reload();
}

timer1.start()
requestAnimationFrame(tick);
function tick(){
    id("output").innerHTML = timer1.get("dig");
    id("slider").style.width = timer1.get()/time *100 + "%";
}

function id(id){
    return document.getElementById(id);
}