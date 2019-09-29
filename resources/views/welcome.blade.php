<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Our Love Story</title>
    <style type="text/css">
        @font-face {
            font-family: dight;
            src: {{ url('love_file/heart/fonts/digital-7_mono.ttf') }}
        }
    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('love_file/heart/css/style.css') }}">
    <script src="http://cdn.bootcss.com/jquery/2.0.0/jquery.js"></script>
    <script type="text/javascript" src="{{ asset('love_file/heart/js/garden.js') }}"></script>
    <script type="text/javascript" src="{{ asset('love_file/heart/js/function.js') }}"></script>
</head>
<body>
<div id="mainDiv">
    <div id="content">
        {{--<div id="code">
            <span class="comments">/**</span><br/>
            <span class="space"/><span class="comments">* We are both programmers,</span><br />
            <span class="space"/><span class="comments">* so I write some code to describe our stories.</span><br />
            <span class="space"/><span class="comments">*/</span><br />
            Boy i = <span class="keyword">new</span> Boy(<span class="string">"Muyy"</span>);<br />
            Girl u = <span class="keyword">new</span> Gril(<span class="string">"Poem"</span>);<br />
            <span class="comments">// Nov 2, 2017, I told you I love you. </span><br />
            i.love(u);<br />
            <span class="comments">// Luckily, you accepted and became my girlfriend eversince.</span><br />
            u.accepted();<br />
            <span class="comments">// Since then, I miss u every day.</span><br />
            i.miss(u);<br />
            <span class="comments">// And take care of u and our love.</span><br />
            i.takeCareOf(u);<br />
            <span class="comments">// You say that you won't be so easy to marry me.</span><br />
            <span class="comments">// So I keep waiting and I have confidence that you will.</span><br />
            <span class="keyword">boolean</span> isHesitate = <span class="keyword">true</span>;<br />
            <span class="keyword">while</span> (isHesitate) {<br />
            <span class="placeholder"/>i.waitFor(u);<br />
            <span class="placeholder"/><span class="comments">// I think it is an important decision</span><br />
            <span class="placeholder"/><span class="comments">// and you should think it over.</span><br />
            <span class="placeholder"/>isHesitate = u.thinkOver();<br />
            }<br />
            <span class="comments">// After a romantic wedding, we will live happily ever after.</span><br />
            i.marry(u);<br />
            i.liveHappilyWith(u);<br />
        </div>--}}
        <div id="loveHeart">
            <canvas id="garden"></canvas>
            <div id="words">
                <div id="messages">
                    Fei, I have fallen in love with you for
                    <div id="elapseClock"></div>
                </div>
                <div id="loveu">
                    Love u forever and ever.<br/>
                    <div class="signature">- W</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    let offsetX = $("#loveHeart").width() / 2;
    let offsetY = $("#loveHeart").height() / 2 - 55;
    let together = new Date();
    together.setFullYear(2017, 9, 27); //2017/9/27
    together.setHours(23);
    together.setMinutes(0);
    together.setSeconds(0);
    together.setMilliseconds(0);
    if (!document.createElement('canvas').getContext) {
        let msg = document.createElement("div");
        msg.id = "errorMsg";
        msg.innerHTML = "Your browser doesn't support HTML5!";
        document.body.appendChild(msg);
        $("#code").css("display", "none");
    } else {
        setTimeout(function() {
            startHeartAnimation();
        }, 1000);

        timeElapse(together);

        adjustCodePosition();
        $("#code").typewriter(); // 打字效果
    }
</script>
</body>
</html>
