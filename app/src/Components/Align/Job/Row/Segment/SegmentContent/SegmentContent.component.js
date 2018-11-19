import React, {Component} from 'react';
import PropTypes from "prop-types";

class SegmentContentComponent extends Component {
    static propTypes = {
        search: PropTypes.object,
        content: PropTypes.string,
        id: PropTypes.any
    };

    constructor(props) {
        super(props);
        this.state = {
            content: this.props.content,
            id: this.props.id,
        }
    }

    static getDerivedStateFromProps(props, state) {
        state.content = props.content;
        state.id = props.id;
        return state;
    }

    render = () => {

        let {id, content} = this.state;
        if (this.props.search && this.props.search.searchResultsDictionary && this.props.search.searchResultsDictionary[id]) {
            this.props.search.searchResultsDictionary[id].occurrences.map(occurrence => {
                const endIndex = occurrence.matchPosition + this.props.search.q.length;
                content = content.slice(0, endIndex) + "</mark>" + content.slice(endIndex);
                if (occurrence.searchProgressiveIndex === this.props.search.featuredSearchResult) {
                    content = content.slice(0, occurrence.matchPosition) + "<mark class='active'>" + content.slice(occurrence.matchPosition);
                } else {
                    content = content.slice(0, occurrence.matchPosition) + "<mark>" + content.slice(occurrence.matchPosition);
                }
            });
        }
        return <p className="segmentBox-content-text" dangerouslySetInnerHTML={{__html: content}}/>
    };
}

export default SegmentContentComponent;
