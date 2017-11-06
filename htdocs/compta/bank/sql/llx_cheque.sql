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
-- Table of "cheque" for customer expert module
-- ============================================================================

create table llx_cheque
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  num_paiement           		varchar(255) NOT NULL,
  emetteur_chq           		varchar(255) NOT NULL,
  bank_chq                              varchar(255) NOT NULL,
  date_chq           			date,
  amount_chq                            double(24,8) NOT NULL default 0,
  fk_customer_account_movement          integer DEFAULT NULL,
  fk_user_author  			integer DEFAULT NULL,
  fk_user_modif   			integer DEFAULT NULL,
  active     	  			tinyint DEFAULT 1  NOT NULL,
  customer_used 			tinyint DEFAULT 0 NOT NULL,
  supplier_used 			tinyint DEFAULT 0 NOT NULL
)ENGINE=innodb;
