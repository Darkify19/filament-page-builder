{{-- Attributes that make a <video> honour a start time, a stop time and looping between
     them. A media fragment (#t=30) only sets where playback begins, and native `loop`
     restarts from zero, so the handlers do the rest from the element's own data. --}}
data-fpb-start="{{ (int) $start }}"
@if ($end) data-fpb-end="{{ (int) $end }}" @endif
@if ($loop) data-fpb-loop="true" @endif
data-fpb-rate="{{ $rate }}"
onloadedmetadata="var s=+this.dataset.fpbStart||0;if(s&&this.currentTime<s)this.currentTime=s;this.playbackRate=+this.dataset.fpbRate||1;"
ontimeupdate="var e=+this.dataset.fpbEnd;if(e&&this.currentTime>=e){if(this.dataset.fpbLoop){this.currentTime=+this.dataset.fpbStart||0;this.play();}else{this.pause();}}"
onended="if(this.dataset.fpbLoop&&!this.loop){this.currentTime=+this.dataset.fpbStart||0;this.play();}"
