# Textends Typecho附加功能插件

## 插件功能简介

### 缩略图
```
// 显示缩略图，需在主题中添加设置
Textends_Plugin::thumbnail($archive, $thumbnailOptions);
```

### 文章
```
// 获取文章列表
Textends_Plugin::contents($options);
```

### 评论
```
// 获取评论列表
Textends_Plugin::comments($options);
// 获取评论作者列表
Textends_Plugin::commentAuthor($options,$format);
```

### 标签
```
// 输出标签云
Textends_Plugin::tags($options, $format);
```

### 分类
```
// 输出多分类
Textends_Plugin::categories($mids, $format);
// 通过分类缩略名获取分类
Textends_Plugin::getCategoryBySlug($slug);
// 通过分类id获取分类
Textends_Plugin::getCategory($mid);
```

### 文章归档
```
// 输出文章归档列表
Textends_Plugin::archives($options);
```

### 导航
```
// 面包屑导航
Textends_Plugin::crumbs($archive, $crumbsOptions);
```