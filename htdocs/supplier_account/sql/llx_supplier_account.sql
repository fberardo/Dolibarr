-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2014	   Juanjo Menent	   <jmenent@2byte.es>
-- Copyright (C) 2016	   Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- Table of "accounts" for supplier expert module
-- ============================================================================

create table llx_supplier_account
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  entity          			integer DEFAULT 1 NOT NULL,
  datec           			datetime,
  tms             			timestamp,
  label           			varchar(255) NOT NULL,
  fk_societe                            integer DEFAULT 0,
  fk_user_author  			integer DEFAULT NULL,
  fk_user_modif   			integer DEFAULT NULL,
  active     	  			tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
