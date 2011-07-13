BEGIN
	# vymazem vsetkych userov, kt. vytvoril dany user-client
	#DELETE FROM users WHERE parent_id = OLD.id;
	#INSERT INTO test2 SET a2 = NEW.a1;
	
	# nastavim jeho packages ako deactivated
	UPDATE client_packages SET is_visible=0 WHERE owner_id = OLD.id;
	
	# deactivate users created by user being deleted
	UPDATE users SET approved=0 WHERE parent_id = OLD.id;
END