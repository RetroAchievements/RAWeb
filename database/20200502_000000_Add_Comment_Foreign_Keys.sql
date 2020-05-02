ALTER TABLE `Comment`
  ADD FOREIGN KEY (`ArticleType`) REFERENCES `Activity` (`ID`),
  ADD FOREIGN KEY (`ArticleID`) REFERENCES `ArticleTypeDimension` (`ArticleTypeID`),
  ADD FOREIGN KEY (`UserID`) REFERENCES `UserAccounts` (`ID`)
;
