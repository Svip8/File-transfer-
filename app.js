// app.js：接收id参数并处理文件上传和列表显示 进度 图标映射
const urlParams = new URLSearchParams(window.location.search);
const spaceId = urlParams.get('id') || 'home';  // 获取 URL 中的 id 参数，如果没有则使用 'home' 作为默认值

const r = new Resumable({
    target: `upload.php?id=${spaceId}`,  // 将空间 ID 添加到上传 URL
    chunkSize: 1 * 1024 * 1024,
    simultaneousUploads: 3,
    testChunks: true,
    fileType: []  // 允许所有类型
});

// 将整个 document.body 设置为拖放区
r.assignDrop(document.body);
document.body.addEventListener('dragover', e => e.preventDefault());
document.body.addEventListener('drop', e => {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
        r.addFiles(e.dataTransfer.files);
    }
});

// 进度条与完成提示元素
const progContainer = document.getElementById('progress-container');
const progBar = document.getElementById('progress-bar');
const tipComplete = document.getElementById('upload-complete');

// 扩展名到 FontAwesome 图标的映射
const extIconMap = {
    jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image', gif: 'fa-file-image',
    zip: 'fa-file-zipper', rar: 'fa-file-zipper',
    mp4: 'fa-file-video', avi: 'fa-file-video', mov: 'fa-file-video',
    pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word',
    xls: 'fa-file-excel', xlsx: 'fa-file-excel',
    txt: 'fa-file-lines', ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint'
};

r.on('fileAdded', () => {
    progContainer.style.display = 'block';
    progBar.style.width = '0%';
    r.upload();
});
r.on('fileProgress', file => {
    const p = Math.floor(file.progress() * 100);
    progBar.style.width = p + '%';
});
r.on('fileSuccess', () => {
    refreshList();
    tipComplete.style.display = 'block';
    setTimeout(() => tipComplete.style.display = 'none', 3000);
});
r.on('fileError', () => {
    progContainer.style.display = 'none';
    alert('上传失败，请重试。');
});

function refreshList() {
    fetch(`list.php?id=${spaceId}`)  // 获取指定空间 ID 的文件列表
        .then(res => res.json())
        .then(files => {
            const grid = document.getElementById('grid');
            grid.innerHTML = '';
            files.forEach(f => {
                const ext = f.name.split('.').pop().toLowerCase();
                const icon = extIconMap[ext] || 'fa-file';
                const a = document.createElement('a');
                a.className = 'file-item';
               // a.href = `uploads/${encodeURIComponent(f.path)}`;// 处理文件路径中的特殊字符
                a.href = `uploads/${spaceId}/${encodeURIComponent(f.path)}`;

                a.download = f.name;// 设置下载文件名
                a.innerHTML = `<i class="fa ${icon}"></i><div class="name">${f.name}</div>`;// 显示文件名
                grid.appendChild(a);
            });
        })
        .catch(err => console.error('列表获取失败：', err));
}

// 页面加载后立即获取一次列表，并每 3 秒自动刷新
window.addEventListener('load', () => {
    refreshList();
    setInterval(refreshList, 3000);
});
