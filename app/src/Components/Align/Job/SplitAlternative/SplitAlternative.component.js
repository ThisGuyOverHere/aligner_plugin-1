import React, {Component} from 'react';
import ProjectActions from "../../../../Actions/Project.actions";
import PropTypes from "prop-types";
import ModalHeader from "../../../Shared/ModalHeader/ModalHeader.component";
import ReactGA from "react-ga";

class SplitAlternative extends Component {
	static propTypes = {
		segment: PropTypes.object,
		inverseSegmentOrder: PropTypes.number,
		jobConf: PropTypes.shape({
			password: PropTypes.string,
			id: PropTypes.any
		})
	};

	constructor(props) {
		super(props);
		this.state = {
			splitModalStatus: false,
			segmentContent: this.props.segment.content_clean,
			calculatedToSplit: [[this.props.segment.content_clean]],
			clearIndexes: [],
		};
	}

	componentDidMount() {

	}

	render = () => {
		const {calculatedToSplit} = this.state;
		return (
			<div>
				<div>
					<div className="overlay" onClick={this.onCloseSplitModal}>
					</div>
					<div className="splitContainer">
						<ModalHeader modalName={"split"}/>
						<div className="content">
							{this.renderItems()}
							<button className="ui button primary splitBtn" disabled={calculatedToSplit.length <= 1}
									onClick={this.onSave}>Split
							</button>
						</div>
					</div>
				</div>
			</div>
		);
	};

	/**
	 * this method correctly render item
	 * return jsx: segments ready for a new split
	 */
	renderItems = () => {
		const {calculatedToSplit} = this.state;
		const {segment} = this.props;

		return <p id="toSplit"
				  style={{textAlign: segment.rtl ? 'right' : 'left', direction: segment.rtl ? 'rtl' : 'ltr'}}>
			{calculatedToSplit.map((item, index) => {
				return <span key={index}>
                         <span onClick={() => this.onSplit(index)}> {item} </span>
					{(calculatedToSplit.length - 1 !== index) &&
					<span className="splitted" onClick={() => this.onReverseSplit(index)}>
                            <i className="icon arrows alternate horizontal"/>
                        </span>}
                        </span>
			})}
		</p>;
	};

	/**
	 * this function take in the clicked segment, and handle char click,
	 * to split the segments at the right position.
	 * @param clickedSegment
	 */
	onSplit = (clickedSegment) => {
		const {calculatedToSplit, clearIndexes} = this.state;
		let newCalculatedToSplit = calculatedToSplit;
		let splitAtChar = window.getSelection().focusOffset;
		const targetSentences = calculatedToSplit[clickedSegment][0];
		console.log("newCalculatedToSplit : ", newCalculatedToSplit);
		console.log("splitAtChar : ", splitAtChar);
		console.log("targetSentences : ", targetSentences);

		if ((splitAtChar !== 0 && (splitAtChar !== targetSentences.length)) && targetSentences.length > 1) {
			// properly split clicked string in two items
			let splittedItems = {
				leftSplit: [targetSentences.substr(0, splitAtChar)],
				rightSplit: [targetSentences.substr(splitAtChar)],
			};
			// calculate splitted index on clear string
			let clearIndex = splitAtChar;
			newCalculatedToSplit.map((item, index) => {
				if (index < clickedSegment) {
					clearIndex = clearIndex + item[0].length;
				}
			});
			// replace old array with the new two
			newCalculatedToSplit[clickedSegment] = splittedItems.leftSplit;
			newCalculatedToSplit.splice((clickedSegment + 1), 0, splittedItems.rightSplit);
			this.setState({
				calculatedToSplit: newCalculatedToSplit,
				clearIndexes: clearIndexes.concat(clearIndex).sort((a, b) => a - b),
			});
		}
	};

	/**
	 * this. method take in separator index and reverse split's effects.
	 * @param clickedSeparator
	 */
	onReverseSplit = (clickedSeparator) => {
		const {calculatedToSplit, clearIndexes} = this.state;
		let newCalculatedToSplit = calculatedToSplit;
		let newClearIndexes = clearIndexes;
		// replace right item with merged content
		newCalculatedToSplit[clickedSeparator + 1] = [calculatedToSplit[clickedSeparator][0] + calculatedToSplit[clickedSeparator + 1][0]];
		// and remove the old one left item.
		newCalculatedToSplit.splice(clickedSeparator, 1);
		// remove index of split from clear indexes array
		newClearIndexes.splice(clickedSeparator, 1);
		this.setState({
			calculatedToSplit: newCalculatedToSplit,
			clearIndexes: newClearIndexes
		});
	};

	onCloseSplitModal = () => {
		ProjectActions.openSegmentToSplit(false);
	};

	onSave = async () => {

		const data = {
			type: this.props.segment.type,
			order: this.props.segment.order,
			inverseOrder: this.props.inverseSegmentOrder,
			positions: this.state.clearIndexes
		};
		ReactGA.event({
			category: 'Interactions',
			action: this.props.jobConf.id,
			label: 'split',
		});
		ProjectActions.splitSegment(this.props.jobConf.id, this.props.jobConf.password, data);
		setTimeout(() => {
			ProjectActions.openSegmentToSplit(false);
		}, 0);
	}
}

export default SplitAlternative;
