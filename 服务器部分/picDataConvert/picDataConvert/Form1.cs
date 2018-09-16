using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace picDataConvert
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }

        byte[] pictureBytes;

        private void button1_Click(object sender, EventArgs e)
        {
            OpenFileDialog OpenFileDialog1 = new OpenFileDialog();
            OpenFileDialog1.Filter = "图片文件|*.jpg;*.jpeg;*.bmp;*.gif;*.png";
            if(OpenFileDialog1.ShowDialog() == DialogResult.OK)
            {
                string path = OpenFileDialog1.FileName;
                FileStream fs = new FileStream(path, FileMode.Open);
                pictureBytes = new byte[fs.Length];
                BinaryReader br = new BinaryReader(fs);
                pictureBytes = br.ReadBytes(Convert.ToInt32(fs.Length));
                MemoryStream ms = new MemoryStream(pictureBytes);
                Bitmap bmpt = new Bitmap(ms);
                Bitmap done = GetThumbnail(bmpt, 200, 200);
                pictureBox1.Image = done;
                trackBar1_Scroll(null, null);
                trackBar1.Enabled = true;
            }
            else
            {
                MessageBox.Show("打开文件失败");
            }
        }




        /// <summary>
        /// 修改图片的大小
        /// </summary>
        /// <param name="b"></param>
        /// <param name="destHeight"></param>
        /// <param name="destWidth"></param>
        /// <returns></returns>
        public Bitmap GetThumbnail(Bitmap b, int destHeight, int destWidth)
        {
            System.Drawing.Image imgSource = b;
            System.Drawing.Imaging.ImageFormat thisFormat = imgSource.RawFormat;
            int sW = 0, sH = 0;
            // 按比例缩放           
            int sWidth = imgSource.Width;
            int sHeight = imgSource.Height;
            if(sWidth != 200 | sHeight != 200)
            {
                MessageBox.Show("图片大小不为200*200，将会自动进行等比缩放处理\r\n" +
                                "建议先把图片处理为200*200大小，再来生成");
            }
            if (sHeight > destHeight || sWidth > destWidth)
            {
                if ((sWidth * destHeight) > (sHeight * destWidth))
                {
                    sW = destWidth;
                    sH = (destWidth * sHeight) / sWidth;
                }
                else
                {
                    sH = destHeight;
                    sW = (sWidth * destHeight) / sHeight;
                }
            }
            else
            {
                sW = sWidth;
                sH = sHeight;
            }
            Bitmap outBmp = new Bitmap(destWidth, destHeight);
            Graphics g = Graphics.FromImage(outBmp);
            g.Clear(Color.Transparent);
            // 设置画布的描绘质量         
            g.CompositingQuality = CompositingQuality.HighQuality;
            g.SmoothingMode = SmoothingMode.HighQuality;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.DrawImage(imgSource, new Rectangle((destWidth - sW) / 2, (destHeight - sH) / 2, sW, sH), 0, 0, imgSource.Width, imgSource.Height, GraphicsUnit.Pixel);
            g.Dispose();
            // 以下代码为保存图片时，设置压缩质量     
            EncoderParameters encoderParams = new EncoderParameters();
            long[] quality = new long[1];
            quality[0] = 100;
            EncoderParameter encoderParam = new EncoderParameter(System.Drawing.Imaging.Encoder.Quality, quality);
            encoderParams.Param[0] = encoderParam;
            imgSource.Dispose();
            return outBmp;
        }

        /// <summary>
        /// 图片二值化
        /// </summary>
        /// <param name="b"></param>
        /// <param name="y"></param>
        /// <returns></returns>
        static Bitmap picConvert(Bitmap b, int y)
        {
            Bitmap black = new Bitmap(b);
            Thresholding(black, y);
            return black;
        }
        static void ToGrey(Bitmap img1)
        {
            for (int i = 0; i < img1.Width; i++)
            {
                for (int j = 0; j < img1.Height; j++)
                {
                    Color pixelColor = img1.GetPixel(i, j);
                    //计算灰度值
                    int grey = (int)(0.299 * pixelColor.R + 0.587 * pixelColor.G + 0.114 * pixelColor.B);
                    Color newColor = Color.FromArgb(grey, grey, grey);
                    img1.SetPixel(i, j, newColor);
                }
            }
        }
        static void Thresholding(Bitmap img1,int y)
        {
            int[] histogram = new int[256];
            int minGrayValue = 255, maxGrayValue = 0;
            //求取直方图
            for (int i = 0; i < img1.Width; i++)
            {
                for (int j = 0; j < img1.Height; j++)
                {
                    Color pixelColor = img1.GetPixel(i, j);
                    histogram[pixelColor.R]++;
                    if (pixelColor.R > maxGrayValue) maxGrayValue = pixelColor.R;
                    if (pixelColor.R < minGrayValue) minGrayValue = pixelColor.R;
                }
            }
            //迭代计算阀值
            int threshold = -1;
            //int newThreshold = (minGrayValue + maxGrayValue) / 2;
            int newThreshold = y;
            for (int iterationTimes = 0; threshold != newThreshold && iterationTimes < 100; iterationTimes++)
            {
                threshold = newThreshold;
                int lP1 = 0;
                int lP2 = 0;
                int lS1 = 0;
                int lS2 = 0;
                //求两个区域的灰度的平均值
                for (int i = minGrayValue; i < threshold; i++)
                {
                    lP1 += histogram[i] * i;
                    lS1 += histogram[i];
                }
                int mean1GrayValue = (lP1 / lS1);
                for (int i = threshold + 1; i < maxGrayValue; i++)
                {
                    lP2 += histogram[i] * i;
                    lS2 += histogram[i];
                }
                //int mean2GrayValue = (lP2 / lS2);
                newThreshold = y;
            }
            //计算二值化
            for (int i = 0; i < img1.Width; i++)
            {
                for (int j = 0; j < img1.Height; j++)
                {
                    Color pixelColor = img1.GetPixel(i, j);
                    if (pixelColor.R > threshold) img1.SetPixel(i, j, Color.FromArgb(255, 255, 255));
                    else img1.SetPixel(i, j, Color.FromArgb(0, 0, 0));
                }
            }
        }

        private void trackBar1_Scroll(object sender, EventArgs e)
        {
            try
            {
                pictureBox2.Image = picConvert((Bitmap)pictureBox1.Image, trackBar1.Value);
                label4.Text = trackBar1.Value.ToString();
                showPicDate(true);
            }
            catch
            {
                pictureBox2.Image = pictureBox1.Image;
                label4.Text = trackBar1.Value.ToString() + "，阈值设置过于极端，处理出现错误";
                showPicDate(false);
            }
        }

        private string d2h(int d)
        {
            if (d < 10)
                return d.ToString();
            else
            {
                switch(d)
                {
                    case 10:
                        return "A";
                    case 11:
                        return "B";
                    case 12:
                        return "C";
                    case 13:
                        return "D";
                    case 14:
                        return "E";
                    case 15:
                        return "F";
                    default:
                        return "err,"+d.ToString()+",err";
                }
            }
        }


        private void showPicDate(bool r)
        {
            if(!r)
            {
                textBox1.Text = "出现错误";
            }
            else
            {
                Bitmap pic = (Bitmap)pictureBox2.Image;
                string pic_result = "";//存储结果
                int bit_temp = 0;  //临时存储用
                for (int x = 0; x < 200; x++)
                {
                    for (int y = 0; y < 200; y += 4)
                    {
                        for (int j = y; j < y + 4; j++)
                        {
                            string rgb = pic.GetPixel(j, x).R.ToString();
                            if (rgb == "255")
                                bit_temp = (bit_temp << 1) + 1;
                            else
                                bit_temp = (bit_temp << 1);
                        }
                        pic_result = pic_result + d2h(bit_temp);
                        bit_temp = 0;
                    }
                }
                textBox1.Text = pic_result;
            }
        }

        private void button2_Click(object sender, EventArgs e)
        {
            if (textBox1.Text.Length > 10)
            {
                Clipboard.SetDataObject(textBox1.Text);
                MessageBox.Show("复制成功，可以直接粘贴到设置网页了");
            }
            else
            {
                MessageBox.Show("数据错误，无法复制");
            }
                
        }
    }
}
