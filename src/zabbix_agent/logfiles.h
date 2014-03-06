/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#ifndef ZABBIX_LOGFILES_H
#define ZABBIX_LOGFILES_H

#include "zbxregexp.h"

int	process_log(char *filename, zbx_uint64_t *lastlogsize, int *mtime, unsigned char *skip_old_data, int *big_rec,
		const char *encoding, zbx_vector_ptr_t *regexps, const char *pattern, const char *output_template,
		int *p_count, int *s_count, zbx_process_value_func_t process_value, const char *server,
		unsigned short port, const char *hostname, const char *key);

int	process_logrt(char *filename, zbx_uint64_t *lastlogsize, int *mtime, unsigned char *skip_old_data,
		int *big_rec, const char *encoding, zbx_vector_ptr_t *regexps, const char *pattern,
		const char *output_template, int *p_count, int *s_count, zbx_process_value_func_t process_value,
		const char *server, unsigned short port, const char *hostname, const char *key);

#endif
