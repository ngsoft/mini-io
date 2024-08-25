<?php

declare(strict_types=1);

namespace NGSOFT\IO;

/**
 * @property string $value
 */
class RgbColor implements CustomColorInterface
{
    use CustomColorTrait;

    public readonly string $value;

    protected string $colorCode = '%d8;2;%d;%d;%d';

    public function __construct(
        protected int $red,
        protected int $green,
        protected int $blue,
        string $name = '',
        bool $foreground = true
    ) {
        foreach ([$this->red, $this->green, $this->blue] as $i)
        {
            static::assertValidRange($i);
        }
        $this->value      = sprintf($this->colorCode, $foreground ? 3 : 4, $this->red, $this->green, $this->blue);

        if ( ! $name)
        {
            $name = sprintf('rgb(%d,%d,%d)', $this->red, $this->green, $this->blue);
        }
        $this->name       = $name;
        $this->foreground = $foreground;
    }

    final public static function createFromRgb(int $r, int $g, int $b, $isBackground = false): static
    {
        return new static($r, $g, $b, foreground: ! $isBackground);
    }

    final public static function createFromHexString(string $hex, $isBackground = false): static
    {
        [$r, $g, $b] = static::convertHexToRgb($hex);
        return static::createFromRgb($r, $g, $b, $isBackground);
    }

    final public static function convertHexToRgb(string $hex): array
    {
        $color = strtoupper(ltrim($hex, '#'));

        $len   = strlen($color);

        if (3 !== $len && 6 !== $len && ! preg_test('#^(?:[0-9A-F]{3}){1,2}$#', $color))
        {
            throw new \InvalidArgumentException("Invalid color code {$hex}");
        }

        if (3 === $len)
        {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        return array_map(fn ($x) => intval($x, 16), str_split($color, 2));
    }

    public static function colorFilter(string $color): ?static
    {
        return self::rgbFilter($color) ?? self::hexFilter($color) ?? self::paletteFilter($color);
    }

    public function getValue(): int|string
    {
        return $this->value;
    }

    protected static function hexFilter(string $color): ?static
    {
        $color = mb_strtoupper($color);

        if (preg_match('%#(?:[0-9A-F]{3}){1,2}%', $color, $matches))
        {
            try
            {
                // here we are in uppercase so BG:
                return static::createFromHexString($matches[0], str_contains($color, 'BG:'));
            } catch (\InvalidArgumentException)
            {
            }
        }

        return null;
    }

    protected static function rgbFilter(string $color): ?static
    {
        $color = mb_strtolower($color);

        if (str_contains($color, 'rgb(') && $str = preg_replace('#[^\d,]#', '', $color))
        {
            $colors = explode(',', $str);

            if (3 === count($colors))
            {
                [$r, $g, $b] = array_map('intval', $colors);

                try
                {
                    return static::createFromRgb($r, $g, $b, str_contains($color, 'bg:'));
                } catch (\InvalidArgumentException)
                {
                }
            }
        }
        return null;
    }

    protected static function paletteFilter(string $color): ?static
    {
        $background = false;
        $color      = mb_strtolower($color);

        if (str_starts_with($color, 'bg:'))
        {
            $background = true;
            $color      = mb_substr($color, 3);
        }

        $values     = self::getPalette()[$color] ?? null;

        if ($values)
        {
            [$r, $g, $b] = $values;
            return self::createFromRgb($r, $g, $b, $background);
        }

        return null;
    }

    protected static function getPalette(): array
    {
        static $palette = [
            'slate-50'             => [248, 250, 252],
            'slate-100'            => [241, 245, 249],
            'slate-200'            => [226, 232, 240],
            'slate-300'            => [203, 213, 225],
            'slate-400'            => [148, 163, 184],
            'slate-500'            => [100, 116, 139],
            'slate-600'            => [71, 85, 105],
            'slate-700'            => [51, 65, 85],
            'slate-800'            => [30, 41, 59],
            'slate-900'            => [15, 23, 42],
            'slate-950'            => [2, 6, 23],
            'gray-50'              => [249, 250, 251],
            'gray-100'             => [243, 244, 246],
            'gray-200'             => [229, 231, 235],
            'gray-300'             => [209, 213, 219],
            'gray-400'             => [156, 163, 175],
            'gray-500'             => [107, 114, 128],
            'gray-600'             => [75, 85, 99],
            'gray-700'             => [55, 65, 81],
            'gray-800'             => [31, 41, 55],
            'gray-900'             => [17, 24, 39],
            'gray-950'             => [3, 7, 18],
            'zinc-50'              => [250, 250, 250],
            'zinc-100'             => [244, 244, 245],
            'zinc-200'             => [228, 228, 231],
            'zinc-300'             => [212, 212, 216],
            'zinc-400'             => [161, 161, 170],
            'zinc-500'             => [113, 113, 122],
            'zinc-600'             => [82, 82, 91],
            'zinc-700'             => [63, 63, 70],
            'zinc-800'             => [39, 39, 42],
            'zinc-900'             => [24, 24, 27],
            'zinc-950'             => [9, 9, 11],
            'neutral-50'           => [250, 250, 250],
            'neutral-100'          => [245, 245, 245],
            'neutral-200'          => [229, 229, 229],
            'neutral-300'          => [212, 212, 212],
            'neutral-400'          => [163, 163, 163],
            'neutral-500'          => [115, 115, 115],
            'neutral-600'          => [82, 82, 82],
            'neutral-700'          => [64, 64, 64],
            'neutral-800'          => [38, 38, 38],
            'neutral-900'          => [23, 23, 23],
            'neutral-950'          => [10, 10, 10],
            'stone-50'             => [250, 250, 249],
            'stone-100'            => [245, 245, 244],
            'stone-200'            => [231, 229, 228],
            'stone-300'            => [214, 211, 209],
            'stone-400'            => [168, 162, 158],
            'stone-500'            => [120, 113, 108],
            'stone-600'            => [87, 83, 78],
            'stone-700'            => [68, 64, 60],
            'stone-800'            => [41, 37, 36],
            'stone-900'            => [28, 25, 23],
            'stone-950'            => [12, 10, 9],
            'red-50'               => [254, 242, 242],
            'red-100'              => [254, 226, 226],
            'red-200'              => [254, 202, 202],
            'red-300'              => [252, 165, 165],
            'red-400'              => [248, 113, 113],
            'red-500'              => [239, 68, 68],
            'red-600'              => [220, 38, 38],
            'red-700'              => [185, 28, 28],
            'red-800'              => [153, 27, 27],
            'red-900'              => [127, 29, 29],
            'red-950'              => [69, 10, 10],
            'orange-50'            => [255, 247, 237],
            'orange-100'           => [255, 237, 213],
            'orange-200'           => [254, 215, 170],
            'orange-300'           => [253, 186, 116],
            'orange-400'           => [251, 146, 60],
            'orange-500'           => [249, 115, 22],
            'orange-600'           => [234, 88, 12],
            'orange-700'           => [194, 65, 12],
            'orange-800'           => [154, 52, 18],
            'orange-900'           => [124, 45, 18],
            'orange-950'           => [67, 20, 7],
            'amber-50'             => [255, 251, 235],
            'amber-100'            => [254, 243, 199],
            'amber-200'            => [253, 230, 138],
            'amber-300'            => [252, 211, 77],
            'amber-400'            => [251, 191, 36],
            'amber-500'            => [245, 158, 11],
            'amber-600'            => [217, 119, 6],
            'amber-700'            => [180, 83, 9],
            'amber-800'            => [146, 64, 14],
            'amber-900'            => [120, 53, 15],
            'amber-950'            => [69, 26, 3],
            'yellow-50'            => [254, 252, 232],
            'yellow-100'           => [254, 249, 195],
            'yellow-200'           => [254, 240, 138],
            'yellow-300'           => [253, 224, 71],
            'yellow-400'           => [250, 204, 21],
            'yellow-500'           => [234, 179, 8],
            'yellow-600'           => [202, 138, 4],
            'yellow-700'           => [161, 98, 7],
            'yellow-800'           => [133, 77, 14],
            'yellow-900'           => [113, 63, 18],
            'yellow-950'           => [66, 32, 6],
            'lime-50'              => [247, 254, 231],
            'lime-100'             => [236, 252, 203],
            'lime-200'             => [217, 249, 157],
            'lime-300'             => [190, 242, 100],
            'lime-400'             => [163, 230, 53],
            'lime-500'             => [132, 204, 22],
            'lime-600'             => [101, 163, 13],
            'lime-700'             => [77, 124, 15],
            'lime-800'             => [63, 98, 18],
            'lime-900'             => [54, 83, 20],
            'lime-950'             => [26, 46, 5],
            'green-50'             => [240, 253, 244],
            'green-100'            => [220, 252, 231],
            'green-200'            => [187, 247, 208],
            'green-300'            => [134, 239, 172],
            'green-400'            => [74, 222, 128],
            'green-500'            => [34, 197, 94],
            'green-600'            => [22, 163, 74],
            'green-700'            => [21, 128, 61],
            'green-800'            => [22, 101, 52],
            'green-900'            => [20, 83, 45],
            'green-950'            => [5, 46, 22],
            'emerald-50'           => [236, 253, 245],
            'emerald-100'          => [209, 250, 229],
            'emerald-200'          => [167, 243, 208],
            'emerald-300'          => [110, 231, 183],
            'emerald-400'          => [52, 211, 153],
            'emerald-500'          => [16, 185, 129],
            'emerald-600'          => [5, 150, 105],
            'emerald-700'          => [4, 120, 87],
            'emerald-800'          => [6, 95, 70],
            'emerald-900'          => [6, 78, 59],
            'emerald-950'          => [2, 44, 34],
            'teal-50'              => [240, 253, 250],
            'teal-100'             => [204, 251, 241],
            'teal-200'             => [153, 246, 228],
            'teal-300'             => [94, 234, 212],
            'teal-400'             => [45, 212, 191],
            'teal-500'             => [20, 184, 166],
            'teal-600'             => [13, 148, 136],
            'teal-700'             => [15, 118, 110],
            'teal-800'             => [17, 94, 89],
            'teal-900'             => [19, 78, 74],
            'teal-950'             => [4, 47, 46],
            'cyan-50'              => [236, 254, 255],
            'cyan-100'             => [207, 250, 254],
            'cyan-200'             => [165, 243, 252],
            'cyan-300'             => [103, 232, 249],
            'cyan-400'             => [34, 211, 238],
            'cyan-500'             => [6, 182, 212],
            'cyan-600'             => [8, 145, 178],
            'cyan-700'             => [14, 116, 144],
            'cyan-800'             => [21, 94, 117],
            'cyan-900'             => [22, 78, 99],
            'cyan-950'             => [8, 51, 68],
            'sky-50'               => [240, 249, 255],
            'sky-100'              => [224, 242, 254],
            'sky-200'              => [186, 230, 253],
            'sky-300'              => [125, 211, 252],
            'sky-400'              => [56, 189, 248],
            'sky-500'              => [14, 165, 233],
            'sky-600'              => [2, 132, 199],
            'sky-700'              => [3, 105, 161],
            'sky-800'              => [7, 89, 133],
            'sky-900'              => [12, 74, 110],
            'sky-950'              => [8, 47, 73],
            'blue-50'              => [239, 246, 255],
            'blue-100'             => [219, 234, 254],
            'blue-200'             => [191, 219, 254],
            'blue-300'             => [147, 197, 253],
            'blue-400'             => [96, 165, 250],
            'blue-500'             => [59, 130, 246],
            'blue-600'             => [37, 99, 235],
            'blue-700'             => [29, 78, 216],
            'blue-800'             => [30, 64, 175],
            'blue-900'             => [30, 58, 138],
            'blue-950'             => [23, 37, 84],
            'indigo-50'            => [238, 242, 255],
            'indigo-100'           => [224, 231, 255],
            'indigo-200'           => [199, 210, 254],
            'indigo-300'           => [165, 180, 252],
            'indigo-400'           => [129, 140, 248],
            'indigo-500'           => [99, 102, 241],
            'indigo-600'           => [79, 70, 229],
            'indigo-700'           => [67, 56, 202],
            'indigo-800'           => [55, 48, 163],
            'indigo-900'           => [49, 46, 129],
            'indigo-950'           => [30, 27, 75],
            'violet-50'            => [245, 243, 255],
            'violet-100'           => [237, 233, 254],
            'violet-200'           => [221, 214, 254],
            'violet-300'           => [196, 181, 253],
            'violet-400'           => [167, 139, 250],
            'violet-500'           => [139, 92, 246],
            'violet-600'           => [124, 58, 237],
            'violet-700'           => [109, 40, 217],
            'violet-800'           => [91, 33, 182],
            'violet-900'           => [76, 29, 149],
            'violet-950'           => [46, 16, 101],
            'purple-50'            => [250, 245, 255],
            'purple-100'           => [243, 232, 255],
            'purple-200'           => [233, 213, 255],
            'purple-300'           => [216, 180, 254],
            'purple-400'           => [192, 132, 252],
            'purple-500'           => [168, 85, 247],
            'purple-600'           => [147, 51, 234],
            'purple-700'           => [126, 34, 206],
            'purple-800'           => [107, 33, 168],
            'purple-900'           => [88, 28, 135],
            'purple-950'           => [59, 7, 100],
            'fuchsia-50'           => [253, 244, 255],
            'fuchsia-100'          => [250, 232, 255],
            'fuchsia-200'          => [245, 208, 254],
            'fuchsia-300'          => [240, 171, 252],
            'fuchsia-400'          => [232, 121, 249],
            'fuchsia-500'          => [217, 70, 239],
            'fuchsia-600'          => [192, 38, 211],
            'fuchsia-700'          => [162, 28, 175],
            'fuchsia-800'          => [134, 25, 143],
            'fuchsia-900'          => [112, 26, 117],
            'fuchsia-950'          => [74, 4, 78],
            'pink-50'              => [253, 242, 248],
            'pink-100'             => [252, 231, 243],
            'pink-200'             => [251, 207, 232],
            'pink-300'             => [249, 168, 212],
            'pink-400'             => [244, 114, 182],
            'pink-500'             => [236, 72, 153],
            'pink-600'             => [219, 39, 119],
            'pink-700'             => [190, 24, 93],
            'pink-800'             => [157, 23, 77],
            'pink-900'             => [131, 24, 67],
            'pink-950'             => [80, 7, 36],
            'rose-50'              => [255, 241, 242],
            'rose-100'             => [255, 228, 230],
            'rose-200'             => [254, 205, 211],
            'rose-300'             => [253, 164, 175],
            'rose-400'             => [251, 113, 133],
            'rose-500'             => [244, 63, 94],
            'rose-600'             => [225, 29, 72],
            'rose-700'             => [190, 18, 60],
            'rose-800'             => [159, 18, 57],
            'rose-900'             => [136, 19, 55],
            'rose-950'             => [76, 5, 25],
            'aliceblue'            => [240, 248, 255],
            'antiquewhite'         => [250, 235, 215],
            'aqua'                 => [0, 255, 255],
            'aquamarine'           => [127, 255, 212],
            'azure'                => [240, 255, 255],
            'beige'                => [245, 245, 220],
            'bisque'               => [255, 228, 196],
            'black'                => [0, 0, 0],
            'blanchedalmond'       => [255, 235, 205],
            'blue'                 => [0, 0, 255],
            'blueviolet'           => [138, 43, 226],
            'brown'                => [165, 42, 42],
            'burlywood'            => [222, 184, 135],
            'cadetblue'            => [95, 158, 160],
            'chartreuse'           => [127, 255, 0],
            'chocolate'            => [210, 105, 30],
            'coral'                => [255, 127, 80],
            'cornflowerblue'       => [100, 149, 237],
            'cornsilk'             => [255, 248, 220],
            'crimson'              => [220, 20, 60],
            'cyan'                 => [0, 255, 255],
            'darkblue'             => [0, 0, 139],
            'darkcyan'             => [0, 139, 139],
            'darkgoldenrod'        => [184, 134, 11],
            'darkgray'             => [169, 169, 169],
            'darkgreen'            => [0, 100, 0],
            'darkgrey'             => [169, 169, 169],
            'darkkhaki'            => [189, 183, 107],
            'darkmagenta'          => [139, 0, 139],
            'darkolivegreen'       => [85, 107, 47],
            'darkorange'           => [255, 140, 0],
            'darkorchid'           => [153, 50, 204],
            'darkred'              => [139, 0, 0],
            'darksalmon'           => [233, 150, 122],
            'darkseagreen'         => [143, 188, 143],
            'darkslateblue'        => [72, 61, 139],
            'darkslategray'        => [47, 79, 79],
            'darkslategrey'        => [47, 79, 79],
            'darkturquoise'        => [0, 206, 209],
            'darkviolet'           => [148, 0, 211],
            'deeppink'             => [255, 20, 147],
            'deepskyblue'          => [0, 191, 255],
            'dimgray'              => [105, 105, 105],
            'dimgrey'              => [105, 105, 105],
            'dodgerblue'           => [30, 144, 255],
            'firebrick'            => [178, 34, 34],
            'floralwhite'          => [255, 250, 240],
            'forestgreen'          => [34, 139, 34],
            'fuchsia'              => [255, 0, 255],
            'gainsboro'            => [220, 220, 220],
            'ghostwhite'           => [248, 248, 255],
            'gold'                 => [255, 215, 0],
            'goldenrod'            => [218, 165, 32],
            'gray'                 => [128, 128, 128],
            'green'                => [0, 128, 0],
            'greenyellow'          => [173, 255, 47],
            'grey'                 => [128, 128, 128],
            'honeydew'             => [240, 255, 240],
            'hotpink'              => [255, 105, 180],
            'indianred'            => [205, 92, 92],
            'indigo'               => [75, 0, 130],
            'ivory'                => [255, 255, 240],
            'khaki'                => [240, 230, 140],
            'lavender'             => [230, 230, 250],
            'lavenderblush'        => [255, 240, 245],
            'lawngreen'            => [124, 252, 0],
            'lemonchiffon'         => [255, 250, 205],
            'lightblue'            => [173, 216, 230],
            'lightcoral'           => [240, 128, 128],
            'lightcyan'            => [224, 255, 255],
            'lightgoldenrodyellow' => [250, 250, 210],
            'lightgray'            => [211, 211, 211],
            'lightgreen'           => [144, 238, 144],
            'lightgrey'            => [211, 211, 211],
            'lightpink'            => [255, 182, 193],
            'lightsalmon'          => [255, 160, 122],
            'lightseagreen'        => [32, 178, 170],
            'lightskyblue'         => [135, 206, 250],
            'lightslategray'       => [119, 136, 153],
            'lightslategrey'       => [119, 136, 153],
            'lightsteelblue'       => [176, 196, 222],
            'lightyellow'          => [255, 255, 224],
            'lime'                 => [0, 255, 0],
            'limegreen'            => [50, 205, 50],
            'linen'                => [250, 240, 230],
            'magenta'              => [255, 0, 255],
            'maroon'               => [128, 0, 0],
            'mediumaquamarine'     => [102, 205, 170],
            'mediumblue'           => [0, 0, 205],
            'mediumorchid'         => [186, 85, 211],
            'mediumpurple'         => [147, 112, 219],
            'mediumseagreen'       => [60, 179, 113],
            'mediumslateblue'      => [123, 104, 238],
            'mediumspringgreen'    => [0, 250, 154],
            'mediumturquoise'      => [72, 209, 204],
            'mediumvioletred'      => [199, 21, 133],
            'midnightblue'         => [25, 25, 112],
            'mintcream'            => [245, 255, 250],
            'mistyrose'            => [255, 228, 225],
            'moccasin'             => [255, 228, 181],
            'navajowhite'          => [255, 222, 173],
            'navy'                 => [0, 0, 128],
            'oldlace'              => [253, 245, 230],
            'olive'                => [128, 128, 0],
            'olivedrab'            => [107, 142, 35],
            'orange'               => [255, 165, 0],
            'orangered'            => [255, 69, 0],
            'orchid'               => [218, 112, 214],
            'palegoldenrod'        => [238, 232, 170],
            'palegreen'            => [152, 251, 152],
            'paleturquoise'        => [175, 238, 238],
            'palevioletred'        => [219, 112, 147],
            'papayawhip'           => [255, 239, 213],
            'peachpuff'            => [255, 218, 185],
            'peru'                 => [205, 133, 63],
            'pink'                 => [255, 192, 203],
            'plum'                 => [221, 160, 221],
            'powderblue'           => [176, 224, 230],
            'purple'               => [128, 0, 128],
            'rebeccapurple'        => [102, 51, 153],
            'red'                  => [255, 0, 0],
            'rosybrown'            => [188, 143, 143],
            'royalblue'            => [65, 105, 225],
            'saddlebrown'          => [139, 69, 19],
            'salmon'               => [250, 128, 114],
            'sandybrown'           => [244, 164, 96],
            'seagreen'             => [46, 139, 87],
            'seashell'             => [255, 245, 238],
            'sienna'               => [160, 82, 45],
            'silver'               => [192, 192, 192],
            'skyblue'              => [135, 206, 235],
            'slateblue'            => [106, 90, 205],
            'slategray'            => [112, 128, 144],
            'slategrey'            => [112, 128, 144],
            'snow'                 => [255, 250, 250],
            'springgreen'          => [0, 255, 127],
            'steelblue'            => [70, 130, 180],
            'tan'                  => [210, 180, 140],
            'teal'                 => [0, 128, 128],
            'thistle'              => [216, 191, 216],
            'tomato'               => [255, 99, 71],
            'turquoise'            => [64, 224, 208],
            'violet'               => [238, 130, 238],
            'wheat'                => [245, 222, 179],
            'white'                => [255, 255, 255],
            'whitesmoke'           => [245, 245, 245],
            'yellow'               => [255, 255, 0],
            'yellowgreen'          => [154, 205, 50],
        ];

        return $palette;
    }
}
