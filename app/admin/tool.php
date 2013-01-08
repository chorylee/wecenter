<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class tool extends AWS_CONTROLLER
{
	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
	}

	/**
	 * 清除系统缓存
	 */
	public function cache_clean_action()
	{
		$this->crumb(AWS_APP::lang()->_t('缓存管理'), "admin/tool/cache_clean/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 501));
		TPL::output('admin/tool/cache_clean');
	}

	public function cache_clean_process_action()
	{
		$this->model('cache')->clean();
		AWS_APP::cache()->clean();
		
		TPL::assign('message', '成功清除网站全部缓存');
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 501));
		TPL::output('admin/tool/cache_clean');
	}
	
	public function reputation_rebuild_action()
	{
		$this->crumb(AWS_APP::lang()->_t('更新威望数据'), "admin/tool/reputation_rebuild/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 504));
		TPL::output('admin/tool/reputation_rebuild');
	}

	public function rebuild_reputation_process_action()
	{
		$this->crumb(AWS_APP::lang()->_t('更新威望数据'), "admin/tool/reputation_rebuild/");
		
		if ($this->is_post())
		{
			$per_page = intval($_POST['per_page']);
			
			HTTP::redirect('?/admin/tool/rebuild_reputation_process/per_page-' . $per_page);
		}
		
		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}
		
		$per_page = intval($_GET['per_page']);
		$page = intval($_GET['page']);
		$interval = intval($_GET['interval']);
		
		$user_count = $this->model('account')->get_user_count();
		
		if ($page * $per_page < $user_count) //未处理完，继续处理
		{
			$this->model('reputation')->calculate((($page * $per_page) - $per_page), $per_page);
			
			$current = ($page * $per_page) > $user_count ? $user_count : ($page * $per_page);
			
			$interval = ($interval == 0) ? 3 : $interval;
			
			$url = '?/admin/tool/rebuild_reputation_process/page-' . ($page + 1) . '__per_page-' . $per_page . '__interval-' . $interval;
			
			H::redirect_msg(AWS_APP::lang()->_t('一共 %s 条数据', $user_count) . ', ' . AWS_APP::lang()->_t('当前处理到 %s 条', $current), $url, $interval);
		}
		else //提示处理完成
		{
			TPL::assign('msg', AWS_APP::lang()->_t('威望数据更新完成'));
			TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 504));
			TPL::output('admin/tool/reputation_rebuild');
		}
	}

	public function bbcode_to_markdown_action()
	{
		if ($this->is_post())
		{
			H::redirect_msg(AWS_APP::lang()->_t('正在准备, 请稍候...'), '?/admin/tool/bbcode_to_markdown/model-question', 2);
		}
		
		if ($model = $_GET['model'])
		{
			$per_page = 100;
			
			$page = $_GET['page'];
			
			if (!$_GET['interval'])
			{
				$_GET['interval'] = 3;
			}
			
			$done = false;
				
			switch ($model)
			{
				case 'question' :
					
					$model_name = AWS_APP::lang()->_t('问题');
					
					if ($question_list = $this->model('question')->search_questions_list(false, array('page' => $page, 'per_page' => $per_page)))
					{
						foreach ($question_list as $key => $val)
						{
							$this->model('question')->update_question_field($val['question_id'], array(
								'question_detail' => FORMAT::bbcode_2_markdown($val['question_detail'])
							));
						}
						
						$page++;
					}
					else
					{
						$page = 1;
						$model = 'answer';
						$model_name = AWS_APP::lang()->_t('回复');
					}
					
					break;
				case 'answer' :
					
					$model_name = AWS_APP::lang()->_t('回复');

					if ($answer_list = $this->model('answer')->get_answer_list(null, $limit, 'answer_id ASC'))
					{
						foreach ($answer_list as $key => $val)
						{
							$this->model('answer')->update_answer_by_id($val['answer_id'], array(
								'answer_content' => FORMAT::bbcode_2_markdown($val['answer_content'])
							));
						}
					
						$page++;
					}
					else
					{
						$page = 1;
						$model = 'topic';
						$model_name = AWS_APP::lang()->_t('话题');
					}
					
					break;
					
				case 'topic' :
					
					$model_name = AWS_APP::lang()->_t('话题');

					if ($topic_list = $this->model('topic')->get_topic_list(null, $limit, false, 'topic_id ASC'))
					{
						foreach ($topic_list as $key => $val)
						{
							$this->model('topic')->update('topic', array(
								'topic_description' => FORMAT::bbcode_2_markdown($val['topic_description'])
							), 'topic_id = ' . intval($val['topic_id']));
						}
					
						$page++;
					}
					else
					{
						$done = true;
					}
					
					break;
			}
			
			if ($done)
			{
				TPL::assign('message', AWS_APP::lang()->_t('BBcode 转换完成'));
			}
			else
			{
				$url = '?/admin/tool/bbcode_to_markdown/model-' . $model . '__page-' . $page . '__per_page-' . $per_page . '__interval-' . $_GET['interval'];
				
				H::redirect_msg(AWS_APP::lang()->_t('正在处理: %s', $model_name) . ', ' . AWS_APP::lang()->_t('批次: %s', $page), $url, $_GET['interval']);
			}
		}
		
		$this->crumb(AWS_APP::lang()->_t('转换 BBcode'), 'admin/tool/bbcode_to_markdown/');
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 505));
		TPL::output('admin/tool/bbcode_to_markdown');
	}

	public function search_index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('更新搜索索引'), "admin/tool/search_index/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 502));
		TPL::output('admin/tool/search_index');
	}

	public function update_search_index_action()
	{
		if ($_GET['per_page'] OR $_POST['per_page'])
		{
			if ($_POST['per_page'])
			{
				$per_page = intval($_POST['per_page']);
			}
			else
			{
				$per_page = intval($_GET['per_page']);
			}
				
			$_GET['page'] = (!$_GET['page']) ? 1 : intval($_GET['page']);
				
			$_GET['interval'] = (!$_GET['interval']) ? 3 : intval($_GET['interval']);
				
			$done = false;
			
			if ($question_list = $this->model('question')->query_all("SELECT question_id, question_content FROM " . get_table('question') . " ORDER BY question_id ASC LIMIT " . calc_page_limit($_GET['page'], $per_page)))
			{
				foreach ($question_list as $key => $val)
				{
					$this->model('search_index')->push_index('question', $val['question_content'], $val['question_id']);
				}
			}
			else
			{
				$done = true;
			}
			
			if ($done)
			{
				TPL::assign('message', AWS_APP::lang()->_t('搜索索引更新完成'));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('正在处理') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/admin/tool/update_search_index/model-' . $_GET['model'] . '__page-' . ++$_GET['page'] . '__per_page-' . $per_page . '__interval-' . $_GET['interval'], $_GET['interval']);
			}
		}
		
		$this->crumb(AWS_APP::lang()->_t('更新搜索索引'), 'admin/tool/update_search_index/');
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 505));
		
		TPL::output('admin/tool/search_index');
	}
}