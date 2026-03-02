<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 1200px;
            height: 630px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }

        .container {
            width: 100%;
            height: 100%;
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .title {
            font-size: 56px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 24px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .description {
            font-size: 24px;
            font-weight: 400;
            color: #a0aec0;
            line-height: 1.5;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .url {
            position: absolute;
            bottom: 48px;
            left: 80px;
            font-size: 18px;
            font-weight: 500;
            color: #4a90d9;
            letter-spacing: 0.5px;
        }

        .accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #4a90d9 0%, #7c3aed 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="accent"></div>
        <div class="title">{{ $title }}</div>
        @if($description)
            <div class="description">{{ $description }}</div>
        @endif
        @if($url)
            <div class="url">{{ $url }}</div>
        @endif
    </div>
</body>
</html>
